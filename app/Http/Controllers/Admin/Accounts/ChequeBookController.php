<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ChequeBook;
use App\Models\ChequeLeaf;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChequeBookController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->current_company_id;
        $books = ChequeBook::with('bankAccount')->withCount('leaves')
            ->where('company_id', $companyId)->latest()->get();
        $leaves = ChequeLeaf::with(['chequeBook','bankAccount','party','payment'])
            ->where('company_id', $companyId)->latest('cheque_date')->latest()->take(80)->get();

        return view('admin.cheques.index', compact('books', 'leaves'));
    }

    public function createBook()
    {
        return view('admin.cheques.book-form', [
            'accounts' => $this->accounts(),
            'nextBookNo' => $this->nextBookNo(),
        ]);
    }

    public function storeBook(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'valid_from' => ['nullable','date'],
            'valid_to' => ['nullable','date','after_or_equal:valid_from'],
            'leaf_count' => ['required','integer','min:1','max:500'],
            'notes' => ['nullable','string'],
        ]);

        ChequeBook::create($data + [
            'company_id' => $companyId,
            'book_no' => $this->nextBookNo(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.cheques.index')->with('success', 'Cheque book created successfully.');
    }

    public function createLeaf()
    {
        return view('admin.cheques.leaf-form', [
            'books' => $this->books(),
        ]);
    }

    public function bookInfo(ChequeBook $chequeBook)
    {
        $this->authorizeCompany($chequeBook->company_id);
        $chequeBook->load('bankAccount');

        return response()->json([
            'id' => $chequeBook->id,
            'book_no' => $chequeBook->book_no,
            'leaf_count' => $chequeBook->leaf_count,
            'used' => $chequeBook->leaves()->count(),
            'remaining' => $chequeBook->remaining_leaves,
            'next_cheque_no' => $this->nextChequeNo($chequeBook),
            'bank' => [
                'id' => $chequeBook->bankAccount?->id,
                'name' => $chequeBook->bankAccount?->bank_name ?: $chequeBook->bankAccount?->account_name,
                'account_name' => $chequeBook->bankAccount?->account_name,
                'account_number' => $chequeBook->bankAccount?->account_number,
                'ifsc_code' => $chequeBook->bankAccount?->ifsc_code,
                'branch_name' => $chequeBook->bankAccount?->branch_name,
            ],
        ]);
    }

    public function storeLeaf(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'cheque_book_id' => ['required', Rule::exists('cheque_books', 'id')->where('company_id', $companyId)],
            'cheque_date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'payee_name' => ['nullable','string','max:255'],
            'amount_words' => ['nullable','string','max:255'],
            'memo' => ['nullable','string','max:255'],
            'validity_months' => ['required','integer','min:1','max:6'],
            'description' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($companyId, $data) {
            $book = ChequeBook::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['cheque_book_id']);
            abort_if($book->remaining_leaves < 1, 422, 'Is cheque book me leaf limit complete ho chuki hai.');

            $date = Carbon::parse($data['cheque_date']);
            $chequeNo = $this->nextChequeNo($book);
            ChequeLeaf::create($data + [
                'company_id' => $companyId,
                'bank_account_id' => $book->bank_account_id,
                'cheque_no' => $chequeNo,
                'clearance_due_date' => $date->copy()->addMonths((int) $data['validity_months'])->toDateString(),
                'status' => 'issued',
                'created_by' => auth()->id(),
            ]);
            $book->update(['next_leaf_no' => (int) $book->next_leaf_no + 1]);
        });

        return redirect()->route('admin.cheques.index')->with('success', 'Cheque leaf issued successfully.');
    }

    public function availableLeaves(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $amount = (float) $request->query('amount');
        $bankAccountId = $request->integer('bank_account_id');
        $partyId = $request->integer('party_id');

        $leaves = ChequeLeaf::with(['chequeBook','bankAccount','party'])
            ->where('company_id', $companyId)
            ->where('status', 'issued')
            ->where('payment_done', false)
            ->whereDate('clearance_due_date', '>=', now()->toDateString())
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))
            ->when($partyId, fn($q) => $q->where(fn($inner) => $inner->whereNull('party_id')->orWhere('party_id', $partyId)))
            ->when($amount > 0, fn($q) => $q->where('amount', round($amount, 2)))
            ->orderBy('clearance_due_date')
            ->get();

        return response()->json($leaves->map(fn(ChequeLeaf $leaf) => [
            'id' => $leaf->id,
            'text' => "{$leaf->cheque_no} | ".($leaf->party?->display_name ?: 'Open Cheque')." | Rs ".number_format((float) $leaf->amount, 2)." | {$leaf->clearance_due_date?->format('d M Y')}",
            'bank_account_id' => $leaf->bank_account_id,
            'amount' => (float) $leaf->amount,
            'book_no' => $leaf->chequeBook?->book_no,
            'cheque_date' => $leaf->cheque_date?->format('d M Y'),
            'clearance_due_date' => $leaf->clearance_due_date?->format('d M Y'),
            'party_id' => $leaf->party_id,
        ])->values());
    }

    public function report(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $filters = $request->validate([
            'from_date' => ['nullable','date'],
            'to_date' => ['nullable','date','after_or_equal:from_date'],
            'party_id' => ['nullable', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'cheque_book_id' => ['nullable', Rule::exists('cheque_books', 'id')->where('company_id', $companyId)],
            'clearance_range' => ['nullable', Rule::in(['this_week','this_month','next_month','custom'])],
            'clearance_from' => ['nullable','date'],
            'clearance_to' => ['nullable','date','after_or_equal:clearance_from'],
        ]);

        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();
        [$clearanceFrom, $clearanceTo] = $this->clearanceRange($filters);

        $leaves = ChequeLeaf::with(['chequeBook','bankAccount','party','payment.allocations'])
            ->where('company_id', $companyId)
            ->whereBetween('cheque_date', [$from, $to])
            ->when($filters['party_id'] ?? null, fn($q, $id) => $q->where('party_id', $id))
            ->when($filters['cheque_book_id'] ?? null, fn($q, $id) => $q->where('cheque_book_id', $id))
            ->when($clearanceFrom && $clearanceTo, fn($q) => $q->whereBetween('clearance_due_date', [$clearanceFrom, $clearanceTo]))
            ->orderBy('clearance_due_date')
            ->get();

        $today = now()->startOfDay();
        $rows = $leaves->map(function (ChequeLeaf $leaf) use ($today) {
            $paid = (float) ($leaf->payment?->allocations?->sum('amount') ?? 0);
            $due = max(0, (float) $leaf->amount - $paid);
            $clearance = $leaf->clearance_due_date?->copy()->startOfDay();

            return [
                'leaf' => $leaf,
                'paid' => $paid,
                'due' => $due,
                'age' => $leaf->cheque_date ? $leaf->cheque_date->diffInDays($today, false) : 0,
                'days_left' => $clearance ? $today->diffInDays($clearance, false) : null,
            ];
        });

        return view('admin.cheques.report', [
            'rows' => $rows,
            'books' => $this->books(),
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'from' => $from,
            'to' => $to,
            'clearanceFrom' => $clearanceFrom,
            'clearanceTo' => $clearanceTo,
            'totals' => [
                'amount' => $rows->sum(fn($row) => (float) $row['leaf']->amount),
                'paid' => $rows->sum('paid'),
                'due' => $rows->sum('due'),
                'pending' => $rows->where('due', '>', 0)->count(),
            ],
        ]);
    }

    public function details(ChequeLeaf $chequeLeaf)
    {
        $this->authorizeCompany($chequeLeaf->company_id);

        return response()->json($this->settlementPayload($chequeLeaf));
    }

    public function print(ChequeLeaf $chequeLeaf)
    {
        $this->authorizeCompany($chequeLeaf->company_id);
        $payload = $this->settlementPayload($chequeLeaf);

        return view('admin.cheques.print', [
            'leaf' => $chequeLeaf->load(['chequeBook','bankAccount','party','payment.allocations','company']),
            'payload' => $payload,
            'company' => $chequeLeaf->company,
        ]);
    }


    private function accounts()
    {
        return BankAccount::where('company_id', auth()->user()->current_company_id)->where('status', 'active')->orderBy('account_name')->get();
    }

    private function books()
    {
        return ChequeBook::with('bankAccount')->where('company_id', auth()->user()->current_company_id)->where('status', 'active')->orderByDesc('id')->get();
    }

    private function nextBookNo(): string
    {
        $next = ChequeBook::where('company_id', auth()->user()->current_company_id)->max('id') + 1;
        return 'CB'.now()->format('Y').str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextChequeNo(ChequeBook $book): string
    {
        return $book->book_no.'-'.str_pad((string) $book->next_leaf_no, 4, '0', STR_PAD_LEFT);
    }

    private function settlementPayload(ChequeLeaf $leaf): array
    {
        $leaf->loadMissing(['chequeBook','bankAccount','party','payment.allocations']);
        $today = now()->startOfDay();
        $clearance = $leaf->clearance_due_date?->copy()->startOfDay();
        $allocations = $leaf->payment?->allocations ?? collect();

        return [
            'cheque' => [
                'id' => $leaf->id,
                'cheque_no' => $leaf->cheque_no,
                'book_no' => $leaf->chequeBook?->book_no,
                'amount' => (float) $leaf->amount,
                'amount_words' => $leaf->amount_words,
                'payee_name' => $leaf->payee_name ?: $leaf->party?->display_name,
                'memo' => $leaf->memo,
                'issue_date' => $leaf->cheque_date?->format('d M Y'),
                'clearance_date' => $leaf->clearance_due_date?->format('d M Y'),
                'clearance_day' => $leaf->clearance_due_date?->format('l'),
                'days_left' => $clearance ? $today->diffInDays($clearance, false) : null,
                'age' => $leaf->cheque_date ? $leaf->cheque_date->diffInDays($today, false) : 0,
                'validity_months' => $leaf->validity_months,
                'status' => ucfirst(str_replace('_', ' ', $leaf->status)),
                'settlement_date' => $leaf->payment?->payment_date?->format('d M Y'),
            ],
            'bank' => [
                'name' => $leaf->bankAccount?->bank_name ?: $leaf->bankAccount?->account_name,
                'account_name' => $leaf->bankAccount?->account_name,
                'account_number' => $leaf->bankAccount?->account_number,
                'ifsc_code' => $leaf->bankAccount?->ifsc_code,
                'branch_name' => $leaf->bankAccount?->branch_name,
            ],
            'party' => [
                'name' => $leaf->party?->display_name,
                'legal_name' => $leaf->party?->legal_name,
                'phone' => $leaf->party?->phone,
                'email' => $leaf->party?->email,
                'gstin' => $leaf->party?->gstin,
                'pan' => $leaf->party?->pan_number,
                'billing_address' => $leaf->party?->billing_address,
                'shipping_address' => $leaf->party?->shipping_address,
                'city' => $leaf->party?->city,
                'state' => $leaf->party?->state,
            ],
            'totals' => [
                'settled' => (float) $allocations->sum('amount'),
                'due' => max(0, (float) $leaf->amount - (float) $allocations->sum('amount')),
            ],
            'bills' => $allocations->map(fn($allocation) => $this->billPayload($allocation))->values(),
            'print_url' => route('admin.cheques.print', $leaf),
        ];
    }

    private function billPayload($allocation): array
    {
        $model = in_array($allocation->bill_model, [SalesInvoice::class, PurchaseBill::class], true)
            ? $allocation->bill_model
            : null;
        $bill = $model ? $model::with(['party','items.item'])->find($allocation->bill_id) : null;

        return [
            'bill_no' => $allocation->bill_no,
            'bill_type' => ucfirst($allocation->bill_type),
            'bill_date' => $allocation->bill_date?->format('d M Y'),
            'bill_total' => (float) $allocation->bill_total,
            'settled_amount' => (float) $allocation->amount,
            'invoice' => $bill ? [
                'invoice_no' => $bill->invoice_no,
                'billing_date' => $bill->billing_date?->format('d M Y'),
                'reference_no' => $bill->reference_no ?? null,
                'subtotal' => (float) ($bill->subtotal ?? 0),
                'discount_amount' => (float) ($bill->discount_amount ?? 0),
                'tax_amount' => (float) ($bill->tax_amount ?? 0),
                'grand_total' => (float) ($bill->grand_total ?? 0),
                'billing_address' => $bill->billing_address ?? null,
                'shipping_address' => $bill->shipping_address ?? null,
            ] : null,
            'items' => $bill?->items?->map(fn($line) => [
                'name' => $line->item?->name ?: 'Item',
                'description' => $line->description,
                'quantity' => (float) $line->quantity,
                'unit' => $line->unit,
                'rate' => (float) $line->unit_price,
                'discount_type' => $line->discount_type,
                'discount_value' => (float) ($line->discount_value ?? 0),
                'discount_amount' => (float) ($line->discount_amount ?? 0),
                'tax_percent' => (float) ($line->tax_percent ?? 0),
                'tax_amount' => (float) ($line->tax_amount ?? 0),
                'line_total' => (float) $line->line_total,
            ])->values() ?? collect(),
        ];
    }

    private function clearanceRange(array $filters): array
    {
        $range = $filters['clearance_range'] ?? null;
        $today = now();

        return match ($range) {
            'this_week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'next_month' => [$today->copy()->addMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->addMonthNoOverflow()->endOfMonth()->toDateString()],
            'custom' => [$filters['clearance_from'] ?? null, $filters['clearance_to'] ?? null],
            default => [null, null],
        };
    }

    private function authorizeCompany(int $companyId): void
    {
        abort_unless($companyId === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
