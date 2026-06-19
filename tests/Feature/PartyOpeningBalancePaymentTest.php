<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyOpeningBalancePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_in_can_partially_settle_a_receivable_opening_balance(): void
    {
        [$user, $company, $party, $account] = $this->paymentContext('receivable', -1000);

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_in',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-06-19',
            'amount' => 400,
            'discount_amount' => 0,
            'payment_mode' => 'UPI',
            'settlement_source' => 'opening_balance',
            'opening_balance_amount' => 400,
        ]);

        $response->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_in']));
        $this->assertSame(-600.0, (float) $party->fresh()->current_balance);
        $this->assertSame(500.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('party_payment_allocations', [
            'party_id' => $party->id,
            'bill_type' => 'opening_balance',
            'amount' => 400,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->getJson(route('admin.party-payments.open-bills', [
                'party_id' => $party->id,
                'payment_type' => 'payment_in',
            ]))
            ->assertOk()
            ->assertJsonPath('opening_balance.total', 1000)
            ->assertJsonPath('opening_balance.paid', 400)
            ->assertJsonPath('opening_balance.remaining', 600)
            ->assertJsonCount(1, 'opening_balance.history');
    }

    public function test_opening_balance_payment_cannot_exceed_remaining_amount(): void
    {
        [$user, $company, $party, $account] = $this->paymentContext('payable', 1000);
        $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_out',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-06-18',
            'amount' => 800,
            'discount_amount' => 0,
            'payment_mode' => 'Cash',
            'settlement_source' => 'opening_balance',
            'opening_balance_amount' => 800,
        ])->assertRedirect(route('admin.party-payments.index', ['type' => 'payment_out']));

        $this->assertSame(200.0, (float) $party->fresh()->current_balance);
        $this->assertSame(-700.0, (float) $account->fresh()->current_balance);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.party-payments.store'), [
            'payment_type' => 'payment_out',
            'party_id' => $party->id,
            'bank_account_id' => $account->id,
            'payment_date' => '2026-06-19',
            'amount' => 250,
            'discount_amount' => 0,
            'payment_mode' => 'Cash',
            'settlement_source' => 'opening_balance',
            'opening_balance_amount' => 250,
        ])->assertStatus(422);

        $this->assertSame(1, PartyPayment::count());
        $this->assertSame(-700.0, (float) $account->fresh()->current_balance);
        $this->assertSame(200.0, (float) $party->fresh()->current_balance);
    }

    private function paymentContext(string $openingType, float $currentBalance): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-001',
            'party_type' => 'both',
            'display_name' => 'Opening Party',
            'opening_balance' => 1000,
            'opening_balance_type' => $openingType,
            'opening_balance_date' => '2026-04-01',
            'current_balance' => $currentBalance,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $account = BankAccount::create([
            'company_id' => $company->id,
            'account_code' => 'CASH-1',
            'account_type' => 'cash',
            'account_name' => 'Main Cash',
            'opening_balance' => 100,
            'current_balance' => 100,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $company, $party, $account];
    }
}
