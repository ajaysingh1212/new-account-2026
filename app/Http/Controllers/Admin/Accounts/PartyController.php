<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Services\EntryVisibilityService;
use App\Services\PartyOutstandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function index(EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
    {
        $parties = $visibility->scopeForUser(
            Party::with('creator')->latest(),
            Party::class
        )->get();
        $balances = $outstanding->balancesByParty($visibility);
        $parties->each(function (Party $party) use ($balances) {
            $balance = $balances->get($party->id, ['receivable' => 0.0, 'payable' => 0.0, 'net' => 0.0]);
            $party->setAttribute('ageing_receivable', $balance['receivable']);
            $party->setAttribute('ageing_payable', $balance['payable']);
            $party->setAttribute('ageing_balance', $balance['net']);
        });

        $summary = [
            'total' => $parties->count(),
            'payable' => (float) $parties->sum('ageing_payable'),
            'receivable' => (float) $parties->sum('ageing_receivable'),
            'active' => $parties->where('status', 'active')->count(),
        ];

        return view('admin.parties.index', compact('parties', 'summary'));
    }

    public function create()
    {
        $party = new Party([
            'party_code' => $this->nextPartyCode(),
            'party_type' => 'both',
            'tax_type' => 'registered',
            'country' => 'India',
            'opening_balance_type' => 'payable',
            'opening_balance_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return view('admin.parties.create', compact('party'));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        if ($request->expectsJson() && (!$request->filled('party_code') || Party::where('company_id', $companyId)->where('party_code', $request->party_code)->exists())) {
            $request->merge(['party_code' => $this->nextPartyCode()]);
        }
        $data = $this->validated($request, $companyId);

        $party = DB::transaction(function () use ($data, $companyId) {
            $openingBalance = (float) ($data['opening_balance'] ?? 0);
            $currentBalance = $data['opening_balance_type'] === 'receivable'
                ? -abs($openingBalance)
                : abs($openingBalance);

            $party = Party::create(array_merge($data, [
                'company_id' => $companyId,
                'current_balance' => $currentBalance,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));

            if ($openingBalance > 0) {
                PartyLedger::create([
                    'company_id' => $companyId,
                    'party_id' => $party->id,
                    'entry_date' => $party->opening_balance_date ?? now()->toDateString(),
                    'entry_type' => 'opening_balance',
                    'reference_type' => Party::class,
                    'reference_id' => $party->id,
                    'reference_no' => $party->party_code,
                    'debit' => $party->opening_balance_type === 'receivable' ? $openingBalance : 0,
                    'credit' => $party->opening_balance_type === 'payable' ? $openingBalance : 0,
                    'balance_after' => $currentBalance,
                    'description' => 'Opening balance entered during party creation.',
                    'created_by' => auth()->id(),
                ]);
            }

            return $party;
        });

        $visibility->syncFromRequest($request, $party);

        AuditLog::log('created', [
            'model' => Party::class,
            'model_id' => $party->id,
            'description' => "Party created: {$party->display_name}",
            'new_values' => $party->toArray(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $party->id, 'display_name' => $party->display_name, 'phone' => $party->phone], 201);
        }
        return redirect()->route('admin.parties.index')->with('success', 'Party created successfully.');
    }

    public function show(Party $party, EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
    {
        $visibility->authorizeView($party);
        $ageingBalance = $outstanding->balanceForParty($visibility, $party->id);
        $statementRows = $outstanding->statementRows($visibility, $party->id);
        $statementSummary = $outstanding->statementSummary($visibility, $party->id);

        return view('admin.parties.show', compact('party', 'ageingBalance', 'statementRows', 'statementSummary'));
    }

    public function edit(Party $party, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($party);

        return view('admin.parties.edit', compact('party'));
    }

    public function update(Request $request, Party $party, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($party);
        $old = $party->toArray();
        $data = $this->validated($request, $party->company_id, $party->id);

        DB::transaction(function () use ($party, $data) {
            $openingBalance = (float) ($data['opening_balance'] ?? 0);
            $openingBalanceValue = $data['opening_balance_type'] === 'receivable'
                ? -abs($openingBalance)
                : abs($openingBalance);

            $nonOpeningBalance = (float) $party->ledgers()
                ->where('entry_type', '!=', 'opening_balance')
                ->sum(DB::raw('credit - debit'));

            $party->update(array_merge($data, [
                'current_balance' => $openingBalanceValue + $nonOpeningBalance,
                'updated_by' => auth()->id(),
            ]));

            $party->ledgers()->where('entry_type', 'opening_balance')->delete();
            if ($openingBalance > 0) {
                PartyLedger::create([
                    'company_id' => $party->company_id,
                    'party_id' => $party->id,
                    'entry_date' => $party->opening_balance_date ?? now()->toDateString(),
                    'entry_type' => 'opening_balance',
                    'reference_type' => Party::class,
                    'reference_id' => $party->id,
                    'reference_no' => $party->party_code,
                    'debit' => $party->opening_balance_type === 'receivable' ? $openingBalance : 0,
                    'credit' => $party->opening_balance_type === 'payable' ? $openingBalance : 0,
                    'balance_after' => $party->current_balance,
                    'description' => 'Opening balance updated from party master.',
                    'created_by' => auth()->id(),
                ]);
            }
        });

        $visibility->syncFromRequest($request, $party);

        AuditLog::log('updated', [
            'model' => Party::class,
            'model_id' => $party->id,
            'description' => "Party updated: {$party->display_name}",
            'old_values' => $old,
            'new_values' => $party->fresh()->toArray(),
        ]);

        return redirect()->route('admin.parties.index')->with('success', 'Party updated successfully.');
    }

    public function destroy(Party $party, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($party);
        AuditLog::log('deleted', [
            'model' => Party::class,
            'model_id' => $party->id,
            'description' => "Party deleted: {$party->display_name}",
        ]);
        $party->delete();

        return redirect()->route('admin.parties.index')->with('success', 'Party deleted successfully.');
    }

    private function validated(Request $request, int $companyId, ?int $partyId = null): array
    {
        return $request->validate([
            'party_code' => ['required','string','max:30', Rule::unique('parties')->where('company_id', $companyId)->ignore($partyId)],
            'party_type' => ['required', Rule::in(['customer','supplier','both'])],
            'display_name' => ['required','string','max:255'],
            'legal_name' => ['nullable','string','max:255'],
            'contact_person' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:30'],
            'alternate_phone' => ['nullable','string','max:30'],
            'whatsapp_number' => ['nullable','string','max:30'],
            'gstin' => ['nullable','string','max:20'],
            'pan_number' => ['nullable','string','max:20'],
            'tan_number' => ['nullable','string','max:20'],
            'cin_number' => ['nullable','string','max:30'],
            'tax_type' => ['required', Rule::in(['registered','composition','unregistered','consumer','overseas'])],
            'place_of_supply' => ['nullable','string','max:120'],
            'billing_address' => ['nullable','string'],
            'shipping_address' => ['nullable','string'],
            'city' => ['nullable','string','max:80'],
            'state' => ['nullable','string','max:80'],
            'pincode' => ['nullable','string','max:15'],
            'country' => ['nullable','string','max:80'],
            'opening_balance' => ['nullable','numeric','min:0'],
            'opening_balance_type' => ['required', Rule::in(['payable','receivable'])],
            'opening_balance_date' => ['nullable','date'],
            'credit_limit' => ['nullable','numeric','min:0'],
            'credit_days' => ['nullable','integer','min:0'],
            'payment_terms' => ['nullable','string','max:255'],
            'bank_name' => ['nullable','string','max:255'],
            'account_holder_name' => ['nullable','string','max:255'],
            'account_number' => ['nullable','string','max:255'],
            'ifsc_code' => ['nullable','string','max:20'],
            'branch_name' => ['nullable','string','max:255'],
            'upi_id' => ['nullable','string','max:255'],
            'status' => ['required', Rule::in(['active','inactive','blocked'])],
            'notes' => ['nullable','string'],
        ]);
    }

    private function nextPartyCode(): string
    {
        $companyId = auth()->user()->current_company_id;
        $next = Party::where('company_id', $companyId)->withTrashed()->count() + 1;

        return 'PTY-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(Party $party): void
    {
        abort_unless($party->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
