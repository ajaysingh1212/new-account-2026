<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseEstimate;
use App\Models\PurchaseEstimateItem;
use App\Models\ProductionBatch;
use App\Models\StockMovement;
use App\Models\SubCostCenter;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SmartPurchaseController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $period = $this->period($request);
        $analysis = $this->analysisRows($period['from'], $period['to'], $visibility);
        $companyId = auth()->user()->current_company_id;

        return view('admin.smart-purchases.index', [
            'analysis' => $analysis,
            'period' => $period,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'invoiceNo' => $this->nextNo(),
            'partyCode' => 'PTY-' . str_pad((string)(Party::where('company_id', $companyId)->withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
            'smartEstimates' => $visibility->scopeForUser(PurchaseEstimate::with('party')->where('is_smart_purchase',true)->latest(), PurchaseEstimate::class)->get(),
        ]);
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'party_id' => ['required', 'array'],
            'party_id.*' => ['required', 'exists:parties,id'],
            'estimate_no' => ['nullable', 'max:30'],
            'billing_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'max:255'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable', 'exists:sub_cost_centers,id'],
            'notes' => ['nullable', 'string'],
            'item_id' => ['required', 'array'],
            'item_id.*' => ['required', 'exists:items,id'],
            'quantity.*' => ['required', 'numeric', 'min:0.001'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
            'tax_percent.*' => ['nullable', 'numeric', 'min:0'],
            'analysis_from' => ['required','date'],
            'analysis_to' => ['required','date','after_or_equal:analysis_from'],
            'attachment' => ['nullable','file','max:4096'],
        ]);

        $companyId = auth()->user()->current_company_id;

        $created = DB::transaction(function () use ($request, $data, $companyId, $visibility) {
            $attachment = $request->hasFile('attachment') ? $request->file('attachment')->store('purchase-estimate-attachments', 'public') : null;
            $groups = collect($request->item_id)->keys()->groupBy(fn($i) => (int)$request->party_id[$i]);
            $estimates = collect();
            foreach ($groups as $partyId => $indexes) {
                abort_unless(Party::where('company_id',$companyId)->whereKey($partyId)->exists(), 422, 'Selected supplier does not belong to this company.');
                $estimate = PurchaseEstimate::create([
                    'company_id' => $companyId, 'party_id' => $partyId,
                    'cost_center_id' => $data['cost_center_id'] ?? null, 'sub_cost_center_id' => $data['sub_cost_center_id'] ?? null,
                    'estimate_no' => $this->nextEstimateNo(), 'estimate_date' => $data['billing_date'],
                    'reference_no' => $data['reference_no'] ?? null, 'notes' => trim('[SMART PURCHASE] '.($data['notes'] ?? '')),
                    'attachment' => $attachment, 'status' => 'draft', 'is_smart_purchase' => true,
                    'analysis_from' => $data['analysis_from'], 'analysis_to' => $data['analysis_to'], 'created_by' => auth()->id(),
                ]);
                $subtotal = $tax = 0;
                foreach ($indexes as $i) {
                    $itemId = $request->item_id[$i];
                $item = Item::where('company_id', $companyId)->findOrFail($itemId);
                $qty = (float) $request->quantity[$i];
                $price = (float) $request->unit_price[$i];
                $taxPercent = (float) ($request->tax_percent[$i] ?? 0);
                $base = $qty * $price;
                $taxAmount = $base * $taxPercent / 100;
                $lineTotal = $base + $taxAmount;

                PurchaseEstimateItem::create([
                    'purchase_estimate_id' => $estimate->id,
                    'item_id' => $item->id,
                    'description' => 'Smart Purchase raw material',
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'unit_price' => $price,
                    'discount_type' => 'percent',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);
                $subtotal += $base;
                $tax += $taxAmount;
                }
                $estimate->update(['subtotal'=>$subtotal,'discount_amount'=>0,'tax_amount'=>$tax,'grand_total'=>$subtotal+$tax]);
                $visibility->syncFromRequest($request, $estimate);
                $estimates->push($estimate);
            }
            return $estimates;
        });

        return redirect()->route('admin.purchase-estimates.index')->with('success', $created->count().' Smart Purchase estimate(s) created. Stock and ledger will post only after final receipt.');
    }

    private function analysisRows(Carbon $from, Carbon $to, EntryVisibilityService $visibility): array
    {
        $companyId = auth()->user()->current_company_id;
        $movements = StockMovement::with('item')->where('company_id', $companyId)
            ->whereBetween('movement_date', [$from->toDateString(),$to->toDateString()])
            ->whereIn('movement_type',['production_consumption','production_consumption_reversal'])
            ->get();
        $batches = ProductionBatch::with('finishedItem')->where('company_id',$companyId)
            ->whereIn('batch_no',$movements->pluck('reference_no')->filter()->unique())->get()->keyBy('batch_no');
        $rawRows = $movements->map(function($move) use ($batches) {
            $sign = $move->movement_type === 'production_consumption' ? 1 : -1;
            $batch = $batches->get($move->reference_no);
            return ['item'=>$move->item,'required_qty'=>$sign*(float)$move->quantity,'valuation'=>$sign*(float)$move->total_value,
                'finished'=>$batch?->finishedItem?->name ?: 'Production '.$move->reference_no,
                'sold_qty'=>$batch ? (float)$batch->quantity : 0,'batch_no'=>$move->reference_no,'date'=>$move->movement_date?->format('d M Y')];
        })->filter(fn($row) => $row['item']);

        $materials = $rawRows->groupBy(fn($row) => $row['item']->id)->map(function ($rows) use ($companyId) {
            $item = $rows->first()['item'];
            $previous = PurchaseBillItem::with('purchaseBill.party')->where('item_id',$item->id)
                ->whereHas('purchaseBill',fn($q)=>$q->where('company_id',$companyId)->whereNotNull('party_id'))
                ->latest('id')->first()?->purchaseBill?->party;
            return [
                'item' => $item,
                'required_qty' => $rows->sum('required_qty'),
                'valuation' => $rows->sum('valuation'),
                'sources' => $rows->pluck('finished')->unique()->join(', '),
                'details' => $rows->groupBy(fn($r)=>$r['batch_no'].'|'.$r['finished'])->map(fn($group)=>[
                    'batch_no'=>$group->first()['batch_no'],'finished'=>$group->first()['finished'],'finished_qty'=>$group->first()['sold_qty'],
                    'consumed_qty'=>$group->sum('required_qty'),'valuation'=>$group->sum('valuation'),'date'=>$group->first()['date'],
                ])->values(),
                'previous_party_id' => $previous?->id,
                'previous_party_name' => $previous?->display_name,
            ];
        })->filter(fn($row)=>$row['required_qty']>0)->values();

        return [
            'materials' => $materials,
            'invoice_total' => $materials->sum('valuation'),
            'raw_total' => $materials->sum('valuation'),
            // Smart Purchase now uses actual production consumption, so there is
            // no sales-invoice total to compare against.
            'difference' => 0,
        ];
    }

    private function period(Request $request): array
    {
        $preset = $request->input('preset', '30');
        $to = $request->filled('to_date') ? Carbon::parse($request->to_date) : today();
        $from = match ($preset) {
            '60' => $to->copy()->subDays(59),
            '90' => $to->copy()->subDays(89),
            'all' => Carbon::create(2000, 1, 1),
            'custom' => $request->filled('from_date') ? Carbon::parse($request->from_date) : $to->copy()->subDays(29),
            default => $to->copy()->subDays(29),
        };

        return ['preset' => $preset, 'from' => $from, 'to' => $to];
    }

    private function nextNo(): string
    {
        return str_pad((string) (PurchaseBill::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }

    private function nextEstimateNo(): string
    {
        $next = PurchaseEstimate::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'SP-'.now()->format('Y').str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }
}
