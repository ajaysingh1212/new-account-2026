<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\EntryVisibilityService;
use App\Services\PartyOutstandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyOutstandingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_outstanding_comes_from_open_bills_not_stored_ledger_balance(): void
    {
        [$user, $party] = $this->context();
        $this->actingAs($user);

        $sale = SalesInvoice::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'sale_type' => 'credit',
            'invoice_no' => 'S-001',
            'billing_date' => '2026-06-18',
            'grand_total' => 14740,
            'created_by' => $user->id,
        ]);
        PurchaseBill::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'purchase_type' => 'credit',
            'invoice_no' => 'P-001',
            'billing_date' => '2026-06-18',
            'grand_total' => 9890,
            'created_by' => $user->id,
        ]);
        PartyLedger::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'entry_date' => '2026-06-18',
            'entry_type' => 'sale_reversal',
            'reference_type' => SalesInvoice::class,
            'reference_id' => $sale->id,
            'reference_no' => $sale->invoice_no,
            'debit' => 0,
            'credit' => 7800,
            'balance_after' => 4329.21,
            'description' => 'Sales ledger reversal before update.',
            'created_by' => $user->id,
        ]);

        $service = app(PartyOutstandingService::class);
        $balance = $service->balanceForParty(app(EntryVisibilityService::class), $party->id, '2026-07-08');
        $statementRows = $service->statementRows(app(EntryVisibilityService::class), $party->id, '2026-07-08');

        $this->assertSame(14740.0, $balance['receivable']);
        $this->assertSame(9890.0, $balance['payable']);
        $this->assertSame(-4850.0, $balance['net']);
        $this->assertCount(2, $statementRows);
        $this->assertFalse($statementRows->contains(fn($row) => $row->entry_type === 'sale_reversal'));
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-001',
            'party_type' => 'both',
            'display_name' => 'Ajay Mehta',
            'opening_balance' => 0,
            'opening_balance_type' => 'payable',
            'current_balance' => 4329.21,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $party];
    }
}
