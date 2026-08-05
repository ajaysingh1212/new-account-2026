<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ChequeBook;
use App\Models\ChequeLeaf;
use App\Models\Party;
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
            'parties' => Party::where('company_id', auth()->user()->current_company_id)->where('status', 'active')->orderBy('display_name')->get(),
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
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
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
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->when($amount > 0, fn($q) => $q->where('amount', round($amount, 2)))
            ->orderBy('clearance_due_date')
            ->get();

        return response()->json($leaves->map(fn(ChequeLeaf $leaf) => [
            'id' => $leaf->id,
            'text' => "{$leaf->cheque_no} | {$leaf->party?->display_name} | Rs ".number_format((float) $leaf->amount, 2)." | {$leaf->clearance_due_date?->format('d M Y')}",
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
        ]);

        $from = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? now()->toDateString();

        $leaves = ChequeLeaf::with(['chequeBook','bankAccount','party','payment.allocations'])
            ->where('company_id', $companyId)
            ->whereBetween('cheque_date', [$from, $to])
            ->when($filters['party_id'] ?? null, fn($q, $id) => $q->where('party_id', $id))
            ->when($filters['cheque_book_id'] ?? null, fn($q, $id) => $q->where('cheque_book_id', $id))
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
            'totals' => [
                'amount' => $rows->sum(fn($row) => (float) $row['leaf']->amount),
                'paid' => $rows->sum('paid'),
                'due' => $rows->sum('due'),
                'pending' => $rows->where('due', '>', 0)->count(),
            ],
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

    private function authorizeCompany(int $companyId): void
    {
        abort_unless($companyId === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
