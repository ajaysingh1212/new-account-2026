<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $returns = $visibility->scopeForUser(SalesReturn::with(['invoice','party','creator'])->latest(), SalesReturn::class)->get();
        return view('admin.sales-returns.index', compact('returns'));
    }

    public function create(EntryVisibilityService $visibility)
    {
        $invoices = $visibility->scopeForUser(
            SalesInvoice::with(['party','items.item'])->latest(),
            SalesInvoice::class
        )->get();

        // ADD THIS
        $invoiceData = [];

        foreach ($invoices as $invoice) {

            $invoiceData[$invoice->id] = [];

            foreach ($invoice->items as $line) {

                $invoiceData[$invoice->id][] = [
                    'id'    => $line->id,
                    'item'  => optional($line->item)->name ?? 'N/A',
                    'qty'   => (float) $line->quantity,
                    'unit'  => $line->unit ?? '',
                    'price' => (float) $line->unit_price,
                    'tax'   => (float) $line->tax_percent,
                ];
            }
        }

        return view('admin.sales-returns.create', [
            'invoices'    => $invoices,
            'invoiceData' => $invoiceData, // ADD THIS
            'returnNo'    => $this->nextNo()
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'sales_invoice_id' => ['required','exists:sales_invoices,id'],
            'return_no' => ['nullable','max:30'],
            'return_date' => ['required','date'],
            'reason' => ['nullable','string'],
            'line_id' => ['required','array'],
            'quantity.*' => ['required','numeric','min:0.001'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $invoice = SalesInvoice::with(['items.item','party'])->findOrFail($data['sales_invoice_id']);
            $return = SalesReturn::create([
                'company_id' => $invoice->company_id,
                'sales_invoice_id' => $invoice->id,
                'party_id' => $invoice->party_id,
                'return_no' => $data['return_no'] ?: $this->nextNo(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = 0;
            foreach ($request->line_id as $i => $lineId) {
                $line = $invoice->items->firstWhere('id', (int) $lineId);
                if (!$line) continue;
                $qty = min((float) $request->quantity[$i], (float) $line->quantity);
                if ($qty <= 0) continue;
                $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
                $taxAmount = (float) $line->tax_amount * $ratio;
                $lineTotal = (float) $line->line_total * $ratio;
                SalesReturnItem::create([
                    'sales_return_id' => $return->id,
                    'sales_invoice_item_id' => $line->id,
                    'item_id' => $line->item_id,
                    'quantity' => $qty,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'selected_units' => collect($line->selected_units ?? [])->take((int) $qty)->values()->all(),
                ]);
                $accounting->moveStock($line->item, [
                    'party_id' => $invoice->party_id,
                    'movement_date' => $return->return_date,
                    'movement_type' => 'sales_return',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_price' => $line->item->purchase_price,
                    'total_value' => $qty * (float) $line->item->purchase_price,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'description' => 'Sales return stock in.',
                ]);
                $subtotal += max(0, $lineTotal - $taxAmount);
                $tax += $taxAmount;
            }

            $return->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'grand_total' => $subtotal + $tax]);
            if ($invoice->sale_type === 'credit' && $invoice->party_id) {
                $accounting->postPartyLedger($invoice->party, [
                    'entry_date' => $return->return_date,
                    'entry_type' => 'sales_return',
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'debit' => 0,
                    'credit' => $return->grand_total,
                    'description' => 'Sales return credit adjustment.',
                ]);
            }
            $visibility->syncFromRequest($request, $return);
        });

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return posted.');
    }

    public function show(SalesReturn $salesReturn, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($salesReturn);
        $salesReturn->load(['invoice','party','items.item']);
        return view('admin.sales-returns.show', ['return' => $salesReturn]);
    }

    private function nextNo(): string
    {
        return 'SR-' . str_pad((string) (SalesReturn::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);
    }
}
