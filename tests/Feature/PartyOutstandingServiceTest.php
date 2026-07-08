<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\BankAccount;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\EntryVisibilityService;
use App\Services\PartyOutstandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyOutstandingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_party_outstanding_subtracts_returns_and_payments_bill_wise(): void
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
        SalesReturn::create([
            'company_id' => $party->company_id,
            'sales_invoice_id' => $sale->id,
            'party_id' => $party->id,
            'return_no' => 'SR-001',
            'return_date' => '2026-06-19',
            'grand_total' => 7800,
            'created_by' => $user->id,
        ]);
        $bank = BankAccount::create([
            'company_id' => $party->company_id,
            'account_code' => 'B-001',
            'account_name' => 'Main Bank',
            'created_by' => $user->id,
        ]);
        $payment = PartyPayment::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'bank_account_id' => $bank->id,
            'payment_date' => '2026-06-20',
            'payment_type' => 'payment_in',
            'amount' => 2610.79,
            'total_amount' => 2610.79,
            'created_by' => $user->id,
        ]);
        PartyPaymentAllocation::create([
            'party_payment_id' => $payment->id,
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'bill_type' => 'sales',
            'bill_model' => SalesInvoice::class,
            'bill_id' => $sale->id,
            'bill_no' => $sale->invoice_no,
            'bill_date' => $sale->billing_date,
            'bill_total' => $sale->grand_total,
            'amount' => 2610.79,
        ]);

        $purchase = PurchaseBill::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'purchase_type' => 'credit',
            'invoice_no' => 'P-001',
            'billing_date' => '2026-06-18',
            'grand_total' => 9890,
            'created_by' => $user->id,
        ]);
        PurchaseReturn::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'purchase_bill_id' => $purchase->id,
            'return_no' => 'PR-001',
            'return_date' => '2026-06-19',
            'grand_total' => 9000,
            'created_by' => $user->id,
        ]);

        $service = app(PartyOutstandingService::class);
        $balance = $service->balanceForParty(app(EntryVisibilityService::class), $party->id, '2026-07-08');
        $rows = $service->billRows(app(EntryVisibilityService::class), $party->id, '2026-07-08');

        $this->assertSame(4329.21, $balance['receivable']);
        $this->assertSame(890.0, $balance['payable']);
        $this->assertSame(-3439.21, $balance['net']);
        $this->assertSame(4329.21, $rows->firstWhere('invoice', 'S-001')['due']);
        $this->assertSame(890.0, $rows->firstWhere('invoice', 'P-001')['due']);
    }

    public function test_settled_bills_are_hidden_from_ageing_rows(): void
    {
        [$user, $party] = $this->context();
        $this->actingAs($user);

        $sale = SalesInvoice::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'sale_type' => 'credit',
            'invoice_no' => 'S-SETTLED',
            'billing_date' => '2026-06-18',
            'grand_total' => 4000,
            'created_by' => $user->id,
        ]);
        $bank = BankAccount::create([
            'company_id' => $party->company_id,
            'account_code' => 'B-002',
            'account_name' => 'Main Bank 2',
            'created_by' => $user->id,
        ]);
        $payment = PartyPayment::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'bank_account_id' => $bank->id,
            'payment_date' => '2026-06-20',
            'payment_type' => 'payment_in',
            'amount' => 4000,
            'total_amount' => 4000,
            'created_by' => $user->id,
        ]);
        PartyPaymentAllocation::create([
            'party_payment_id' => $payment->id,
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'bill_type' => 'sales',
            'bill_model' => SalesInvoice::class,
            'bill_id' => $sale->id,
            'bill_no' => $sale->invoice_no,
            'bill_date' => $sale->billing_date,
            'bill_total' => $sale->grand_total,
            'amount' => 4000,
        ]);

        $rows = app(PartyOutstandingService::class)->billRows(app(EntryVisibilityService::class), $party->id, '2026-07-08');

        $this->assertFalse($rows->contains(fn(array $row) => $row['invoice'] === 'S-SETTLED'));
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
            'current_balance' => -4329.21,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $party];
    }
}
