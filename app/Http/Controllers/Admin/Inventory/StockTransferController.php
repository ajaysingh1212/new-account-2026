<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyMerge;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    // ── LIST ──────────────────────────────────────────────────
    public function index()
    {
        $user      = auth()->user();
        $companyId = $user->current_company_id;

        $transfers = StockTransfer::with(['fromCompany', 'toCompany', 'creator'])
            ->where(function ($q) use ($companyId) {
                $q->where('from_company_id', $companyId)
                  ->orWhere('to_company_id', $companyId);
            })
            ->latest()
            ->get();

        return view('admin.stock-transfers.index', compact('transfers'));
    }

    // ── CREATE FORM ───────────────────────────────────────────
    public function create()
    {
        $user      = auth()->user();
        $companyId = $user->current_company_id;

        // Companies available to transfer TO
        if ($user->isSuperAdmin()) {
            // SuperAdmin: all active companies except current
            $toCompanies = Company::where('is_active', true)
                ->where('id', '!=', $companyId)
                ->orderBy('name')
                ->get();
        } else {
            // Normal admin/user: only merged companies
            $mergedIds = CompanyMerge::getMergedCompanyIds($companyId);
            if (empty($mergedIds)) {
                return view('admin.stock-transfers.create', [
                    'toCompanies'   => collect(),
                    'finishedItems' => collect(),
                    'today'         => now()->format('Y-m-d'),
                ])->with('warning', 'Aapki company kisi bhi company ke saath merge nahi hai. Pehle SuperAdmin se merge karwayein.');
            }
            $toCompanies = Company::whereIn('id', $mergedIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        // Finished goods with stock > 0 for current company
        $finishedItems = Item::where('company_id', $companyId)
            ->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->where('track_stock', true)
            ->where('current_stock', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'item_code', 'unit', 'current_stock', 'sale_price']);

        return view('admin.stock-transfers.create', [
            'toCompanies'   => $toCompanies,
            'finishedItems' => $finishedItems,
            'today'         => now()->format('Y-m-d'),
        ]);
    }

    // ── STORE ─────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->current_company_id;

        $request->validate([
            'transfer_date'  => ['required', 'date'],
            'to_company_id'  => ['required', 'exists:companies,id', 'different:from_company_id'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.item_id'  => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        // Verify to_company is allowed
        if (!$user->isSuperAdmin()) {
            $mergedIds = CompanyMerge::getMergedCompanyIds($companyId);
            if (!in_array((int)$request->to_company_id, $mergedIds)) {
                return back()->withErrors(['to_company_id' => 'Ye company aapke saath merge nahi hai.'])->withInput();
            }
        }

        DB::transaction(function () use ($request, $companyId) {
            $transfer = StockTransfer::create([
                'from_company_id' => $companyId,
                'to_company_id'   => $request->to_company_id,
                'transfer_no'     => StockTransfer::nextNumber(),
                'transfer_date'   => $request->transfer_date,
                'notes'           => $request->notes,
                'status'          => 'pending',
                'created_by'      => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                $item = Item::where('id', $row['item_id'])
                    ->where('company_id', $companyId)
                    ->firstOrFail();

                // Validate quantity <= available stock
                if ((float)$row['quantity'] > (float)$item->current_stock) {
                    throw new \Exception("Item '{$item->name}' ke liye available stock se jyada quantity dali hai.");
                }

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'item_id'           => $item->id,
                    'quantity'          => $row['quantity'],
                    'stock_before'      => $item->current_stock,
                    'unit_price'        => $item->sale_price ?? 0,
                ]);
            }
        });

        return redirect()->route('admin.stock-transfers.index')
            ->with('success', 'Stock transfer request bhej di gayi. To-company ka admin approve karega.');
    }

    // ── SHOW ──────────────────────────────────────────────────
    public function show(StockTransfer $stockTransfer)
    {
        $this->authorizeView($stockTransfer);
        $stockTransfer->load(['fromCompany', 'toCompany', 'creator', 'approvedBy', 'items.item']);
        return view('admin.stock-transfers.show', compact('stockTransfer'));
    }

    // ── APPROVE ───────────────────────────────────────────────
    public function approve(Request $request, StockTransfer $stockTransfer)
    {
        $this->authorizeApprove($stockTransfer);

        if (!$stockTransfer->isPending()) {
            return back()->with('error', 'Sirf pending transfers approve ho sakte hain.');
        }

        DB::transaction(function () use ($stockTransfer) {
            foreach ($stockTransfer->items as $line) {
                $fromItem = Item::where('id', $line->item_id)
                    ->where('company_id', $stockTransfer->from_company_id)
                    ->lockForUpdate()->first();

                if (!$fromItem) continue;

                // Deduct from sender
                $newFromStock  = max(0, $fromItem->current_stock - $line->quantity);
                $newFromValue  = $newFromStock * ($line->unit_price ?: $fromItem->sale_price ?: 0);
                $fromItem->update(['current_stock' => $newFromStock, 'stock_value' => $newFromValue]);

                StockMovement::create([
                    'company_id'     => $stockTransfer->from_company_id,
                    'item_id'        => $fromItem->id,
                    'movement_date'  => $stockTransfer->transfer_date,
                    'movement_type'  => 'transfer_out',
                    'direction'      => 'out',
                    'quantity'       => $line->quantity,
                    'unit_price'     => $line->unit_price,
                    'total_value'    => $line->quantity * $line->unit_price,
                    'stock_after'    => $newFromStock,
                    'value_after'    => $newFromValue,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $stockTransfer->id,
                    'reference_no'   => $stockTransfer->transfer_no,
                    'description'    => 'Transfer to ' . $stockTransfer->toCompany->name,
                    'created_by'     => auth()->id(),
                ]);

                // Add to receiver — find or create the same item in receiver's company
                $toItem = Item::where('company_id', $stockTransfer->to_company_id)
                    ->where('item_code', $fromItem->item_code)
                    ->lockForUpdate()->first();

                if (!$toItem) {
                    // Clone item to receiver company
                    $toItem = $fromItem->replicate();
                    $toItem->company_id    = $stockTransfer->to_company_id;
                    $toItem->current_stock = 0;
                    $toItem->stock_value   = 0;
                    $toItem->opening_stock = 0;
                    $toItem->created_by    = auth()->id();
                    $toItem->save();
                }

                $newToStock = $toItem->current_stock + $line->quantity;
                $newToValue = $newToStock * ($line->unit_price ?: $toItem->sale_price ?: 0);
                $toItem->update(['current_stock' => $newToStock, 'stock_value' => $newToValue]);

                StockMovement::create([
                    'company_id'     => $stockTransfer->to_company_id,
                    'item_id'        => $toItem->id,
                    'movement_date'  => $stockTransfer->transfer_date,
                    'movement_type'  => 'transfer_in',
                    'direction'      => 'in',
                    'quantity'       => $line->quantity,
                    'unit_price'     => $line->unit_price,
                    'total_value'    => $line->quantity * $line->unit_price,
                    'stock_after'    => $newToStock,
                    'value_after'    => $newToValue,
                    'reference_type' => StockTransfer::class,
                    'reference_id'   => $stockTransfer->id,
                    'reference_no'   => $stockTransfer->transfer_no,
                    'description'    => 'Transfer from ' . $stockTransfer->fromCompany->name,
                    'created_by'     => auth()->id(),
                ]);
            }

            $stockTransfer->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('admin.stock-transfers.show', $stockTransfer)
            ->with('success', 'Transfer approve ho gaya. Stock dono companies me update ho gaya.');
    }

    // ── REJECT ────────────────────────────────────────────────
    public function reject(Request $request, StockTransfer $stockTransfer)
    {
        $this->authorizeApprove($stockTransfer);

        if (!$stockTransfer->isPending()) {
            return back()->with('error', 'Sirf pending transfers reject ho sakte hain.');
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $stockTransfer->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->route('admin.stock-transfers.show', $stockTransfer)
            ->with('success', 'Transfer reject kar diya gaya.');
    }

    // ── AJAX: item stock ──────────────────────────────────────
    public function itemStock(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $items = Item::where('company_id', $companyId)
            ->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->where('track_stock', true)
            ->where('current_stock', '>', 0)
            ->whereNotIn('id', $request->input('exclude', []))
            ->orderBy('name')
            ->get(['id', 'name', 'item_code', 'unit', 'current_stock', 'sale_price']);

        return response()->json($items);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function authorizeView(StockTransfer $transfer): void
    {
        $user      = auth()->user();
        $companyId = $user->current_company_id;
        abort_unless(
            $user->isSuperAdmin() || $user->isAdmin() ||
            $transfer->from_company_id === $companyId ||
            $transfer->to_company_id === $companyId,
            403
        );
    }

    private function authorizeApprove(StockTransfer $transfer): void
    {
        $user      = auth()->user();
        $companyId = $user->current_company_id;
        // Only the receiving company's admin or superadmin can approve
        abort_unless(
            $user->isSuperAdmin() ||
            ($transfer->to_company_id === $companyId && ($user->isAdmin() || $user->can('stock_transfers.approve'))),
            403
        );
    }
}
