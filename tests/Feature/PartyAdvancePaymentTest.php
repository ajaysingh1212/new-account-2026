<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyAdvancePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_in_creates_a_customer_advance_and_exposes_it_for_future_allocation(): void
    {
        [$user, $party, $account] = $this->context();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-16',
            'amount' => 750,
            'discount_amount' => 0,
            'payment_mode' => 'UPI',
            'settlement_source' => 'advance',
            'description' => 'Advance received from customer.',
        ]);

        $response->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));

        $payment = PartyPayment::firstOrFail();
        $this->assertDatabaseHas('party_advances', [
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'party_payment_id' => $payment->id,
            'direction' => 'in',
            'original_amount' => 750,
            'remaining_amount' => 750,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->getJson(route('admin.party-payments.open-bills', [
                'party_id' => $party->id,
                'payment_type' => 'payment_in',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'available_advances')
            ->assertJsonPath('available_advances.0.remaining_amount', 750);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.parties.show', $party))
            ->assertOk()
            ->assertSeeText('Advance Payments')
            ->assertSeeText('Customer Advance')
            ->assertSeeText('Rs 750.00');
    }

    public function test_payment_out_creates_a_supplier_advance_and_exposes_it_for_purchase_workflow(): void
    {
        [$user, $party, $account] = $this->context();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_out',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-07-16',
            'amount' => 1200,
            'discount_amount' => 0,
            'payment_mode' => 'Cash',
            'settlement_source' => 'advance',
            'description' => 'Advance paid to supplier.',
        ]);

        $response->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_out']));

        $payment = PartyPayment::firstOrFail();
        $this->assertDatabaseHas('party_advances', [
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'party_payment_id' => $payment->id,
            'direction' => 'out',
            'original_amount' => 1200,
            'remaining_amount' => 1200,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->getJson(route('admin.party-advances.available', [
                'party_id' => $party->id,
                'flow' => 'purchase',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'advances')
            ->assertJsonPath('advances.0.remaining_amount', 1200);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-ADV-001',
            'party_type' => 'both',
            'display_name' => 'Advance Party',
            'opening_balance' => 0,
            'opening_balance_type' => 'payable',
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $account = BankAccount::create([
            'company_id' => $company->id,
            'account_code' => 'BANK-001',
            'account_type' => 'bank',
            'account_name' => 'Main Bank',
            'opening_balance' => 5000,
            'current_balance' => 5000,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $party, $account];
    }
}
