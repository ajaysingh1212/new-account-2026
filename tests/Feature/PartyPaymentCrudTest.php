<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\PartyOpeningBalanceAdjustment;
use App\Models\PartyPayment;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyPaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_in_adjustment_updates_opening_balance_and_saves_audit_details(): void
    {
        [$user, $party, $account] = $this->openingBalanceContext();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-20',
            'amount' => 1200,
            'discount_amount' => 0,
            'payment_mode' => 'UPI',
            'settlement_source' => 'opening_balance',
            'opening_balance_amount' => 1200,
            'adjustment_type' => 'increase',
            'adjustment_amount' => 200,
            'adjustment_note' => 'Manual receivable correction',
        ]);

        $response->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $party = $party->fresh();
        $this->assertSame(1200.0, (float) $party->opening_balance);
        $this->assertSame(0.0, (float) $party->current_balance);

        $adjustment = PartyOpeningBalanceAdjustment::first();
        $this->assertNotNull($adjustment);
        $this->assertSame(1000.0, (float) $adjustment->previous_amount);
        $this->assertSame(200.0, (float) $adjustment->adjustment_amount);
        $this->assertSame(1200.0, (float) $adjustment->new_amount);
        $this->assertSame('increase', $adjustment->direction);
        $this->assertSame('Manual receivable correction', $adjustment->reason);
        $this->assertSame($user->name, $adjustment->creator?->name);

        $this->actingAs($user)->withoutMiddleware()
            ->getJson(route('admin.party-payments.open-bills', [
                'party_id' => $party->id,
                'payment_type' => 'payment_in',
            ]))
            ->assertOk()
            ->assertJsonPath('opening_balance.total', 1200)
            ->assertJsonPath('opening_balance.paid', 1200)
            ->assertJsonPath('opening_balance.remaining', 0)
            ->assertJsonPath('adjustment_history.0.previous_amount', 1000)
            ->assertJsonPath('adjustment_history.0.adjustment_amount', 200)
            ->assertJsonPath('adjustment_history.0.new_amount', 1200)
            ->assertJsonPath('adjustment_history.0.reason', 'Manual receivable correction');
    }

    public function test_payment_update_reverts_old_effects_and_applies_new_ones(): void
    {
        [$user, $party, $account, $invoiceA, $invoiceB] = $this->invoiceContext();

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-18',
            'amount' => 300,
            'discount_amount' => 0,
            'payment_mode' => 'Cash',
            'settlement_source' => 'bills',
            'allocations' => [
                ['bill_id' => $invoiceA->id, 'amount' => 300],
            ],
        ])->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $payment = PartyPayment::firstOrFail();
        $this->assertSame(-500.0, (float) $party->fresh()->current_balance);
        $this->assertSame(1300.0, (float) $account->fresh()->current_balance);

        $this->actingAs($user)->withoutMiddleware()->put(route('admin.party-payments.update', $payment), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-19',
            'amount' => 450,
            'discount_amount' => 0,
            'payment_mode' => 'UPI',
            'settlement_source' => 'bills',
            'allocations' => [
                ['bill_id' => $invoiceB->id, 'amount' => 450],
            ],
        ])->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $payment = $payment->fresh();
        $this->assertSame(450.0, (float) $payment->amount);
        $this->assertSame('UPI', $payment->payment_mode);
        $this->assertSame('2026-07-19', $payment->payment_date?->format('Y-m-d'));
        $this->assertSame(-350.0, (float) $party->fresh()->current_balance);
        $this->assertSame(1450.0, (float) $account->fresh()->current_balance);

        $this->assertDatabaseHas('party_payment_allocations', [
            'party_payment_id' => $payment->id,
            'bill_id' => $invoiceB->id,
            'amount' => 450,
        ]);
        $this->assertDatabaseMissing('party_payment_allocations', [
            'party_payment_id' => $payment->id,
            'bill_id' => $invoiceA->id,
            'amount' => 300,
        ]);

        $this->assertSame(1, PartyLedger::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->count());
        $this->assertSame(1, BankTransaction::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->count());
    }

    public function test_payment_delete_reverts_ledger_bank_and_allocations(): void
    {
        [$user, $party, $account, $invoiceA] = $this->invoiceContext();

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-18',
            'amount' => 250,
            'discount_amount' => 0,
            'payment_mode' => 'Cash',
            'settlement_source' => 'bills',
            'allocations' => [
                ['bill_id' => $invoiceA->id, 'amount' => 250],
            ],
        ])->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $payment = PartyPayment::firstOrFail();
        $this->assertSame(-550.0, (float) $party->fresh()->current_balance);
        $this->assertSame(1250.0, (float) $account->fresh()->current_balance);

        $this->actingAs($user)->withoutMiddleware()
            ->delete(route('admin.party-payments.destroy', $payment))
            ->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $this->assertDatabaseMissing('party_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('party_payment_allocations', ['party_payment_id' => $payment->id]);
        $this->assertSame(-800.0, (float) $party->fresh()->current_balance);
        $this->assertSame(1000.0, (float) $account->fresh()->current_balance);
        $this->assertSame(0, PartyLedger::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->count());
        $this->assertSame(0, BankTransaction::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->count());
    }

    private function openingBalanceContext(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin', 'name' => 'Receivable Admin']);
        $company = Company::create(['name' => 'Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-OB-001',
            'party_type' => 'both',
            'display_name' => 'Opening Balance Party',
            'opening_balance' => 1000,
            'opening_balance_type' => 'receivable',
            'opening_balance_date' => '2026-04-01',
            'current_balance' => -1000,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $account = BankAccount::create([
            'company_id' => $company->id,
            'account_code' => 'BANK-OB-001',
            'account_type' => 'bank',
            'account_name' => 'Adjustment Bank',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $party, $account];
    }

    private function invoiceContext(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-INV-001',
            'party_type' => 'customer',
            'display_name' => 'Invoice Party',
            'opening_balance' => 800,
            'opening_balance_type' => 'receivable',
            'opening_balance_date' => '2026-04-01',
            'current_balance' => -800,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $account = BankAccount::create([
            'company_id' => $company->id,
            'account_code' => 'BANK-INV-001',
            'account_type' => 'bank',
            'account_name' => 'Collections Bank',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $invoiceA = SalesInvoice::create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'invoice_no' => 'SI-1001',
            'billing_date' => '2026-07-10',
            'sale_type' => 'credit',
            'grand_total' => 300,
            'created_by' => $user->id,
        ]);

        $invoiceB = SalesInvoice::create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'invoice_no' => 'SI-1002',
            'billing_date' => '2026-07-11',
            'sale_type' => 'credit',
            'grand_total' => 450,
            'created_by' => $user->id,
        ]);

        return [$user, $party, $account, $invoiceA, $invoiceB];
    }
}
