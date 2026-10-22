<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\EntryVisibility;
use App\Models\ExpenseLedger;
use App\Models\OtherTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherTransactionDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_deletion_reverses_income_and_updates_later_balances(): void
    {
        [$bank, $ledger, $entry] = $this->context();
        $this->get(route('admin.other-transactions.index'))->assertOk()
            ->assertSee(route('admin.other-transactions.destroy', $entry))->assertSee('value="1" checked', false);
        $this->get(route('admin.other-transactions.show', $entry))->assertOk()->assertSee('Revert linked transactions');
        $this->approve($entry);
        $later = $this->copy($entry, 'expense', 20);
        $this->approve($later);
        $this->assertSame(1098.0, (float) $bank->fresh()->current_balance);
        $posting = $this->posting($entry);
        $this->delete(route('admin.other-transactions.destroy', $entry))->assertRedirect(route('admin.other-transactions.index'));
        $this->assertSoftDeleted($entry);
        $this->assertSame(980.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
        $this->assertSame(980.0, (float) $this->posting($later)->balance_after);
        $this->assertSame(980.0, (float) $later->fresh()->bank_balance_after);
        $this->assertSame(30.0, (float) $later->fresh()->ledger_balance_after);
        $this->assertDatabaseMissing('bank_transactions', ['id' => $posting->id]);
        $this->assertDatabaseMissing('entry_visibilities', ['entry_type' => BankTransaction::class, 'entry_id' => $posting->id]);
        $this->assertDatabaseMissing('entry_visibilities', ['entry_type' => OtherTransaction::class, 'entry_id' => $entry->id]);
        $audit = AuditLog::where('action', 'deleted')->where('model', OtherTransaction::class)->firstOrFail();
        $this->assertTrue($audit->new_values['revert_transactions']);
        $this->delete(route('admin.other-transactions.destroy', $entry))->assertNotFound();
        $this->post(route('admin.other-transactions.approve', $entry))->assertNotFound();
        $this->assertSame(980.0, (float) $bank->fresh()->current_balance);
    }

    public function test_checked_deletion_reverses_expense_and_keeps_unrelated_postings(): void
    {
        [$bank, , $entry] = $this->context('expense');
        $this->approve($entry);
        $unrelated = BankTransaction::create([
            'company_id' => $entry->company_id, 'bank_account_id' => $bank->id,
            'transaction_date' => '2026-10-22', 'transaction_type' => 'manual_adjustment',
            'direction' => 'in', 'amount' => 23, 'balance_after' => 905,
        ]);
        $bank->update(['current_balance' => 905]);
        $this->delete(route('admin.other-transactions.destroy', $entry), ['revert_transactions' => 1])->assertRedirect();
        $this->assertSame(1023.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(1023.0, (float) $unrelated->fresh()->balance_after);
        $this->assertSame(23.0, (float) $unrelated->fresh()->amount);
    }

    public function test_unchecked_deletion_preserves_postings_and_future_ledger_balances_for_both_kinds(): void
    {
        foreach (['income', 'expense'] as $kind) {
            [$bank, $ledger, $entry] = $this->context($kind);
            $this->approve($entry);
            $posting = $this->posting($entry);
            $balance = (float) $bank->fresh()->current_balance;
            $this->delete(route('admin.other-transactions.destroy', $entry), ['revert_transactions' => 0])->assertRedirect();
            $this->assertSoftDeleted($entry);
            $this->assertSame($balance, (float) $bank->fresh()->current_balance);
            $this->assertSame($posting->toArray(), $posting->fresh()->toArray());
            $this->assertDatabaseHas('entry_visibilities', ['entry_type' => BankTransaction::class, 'entry_id' => $posting->id]);
            $later = $this->copy($entry, 'income', 20);
            $this->approve($later);
            $this->assertSame(50.0 + ($kind === 'income' ? 118 : -118) + 20, (float) $later->fresh()->ledger_balance_after);
            $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
            $this->get(route('admin.other-transactions.show', $entry))->assertNotFound();
        }
    }

    public function test_pending_and_rejected_entries_delete_without_balance_changes(): void
    {
        [$bank, , $entry] = $this->context();
        $rejected = $this->copy($entry, 'expense', 20);
        $rejected->update(['status' => 'rejected']);
        foreach ([$entry, $rejected] as $row) {
            $this->delete(route('admin.other-transactions.destroy', $row))->assertRedirect();
            $this->assertSoftDeleted($row);
        }
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
        $this->assertDatabaseCount('bank_transactions', 0);
    }

    public function test_missing_ledger_rolls_back_reversal_and_invalid_checkbox_is_rejected(): void
    {
        [$bank, $ledger, $entry] = $this->context();
        $this->approve($entry);
        $this->delete(route('admin.other-transactions.destroy', $entry), ['revert_transactions' => 'invalid'])->assertSessionHasErrors('revert_transactions');
        $other = Company::create(['name' => 'Other', 'created_by' => auth()->id()]);
        $ledger->update(['company_id' => $other->id]);
        $this->delete(route('admin.other-transactions.destroy', $entry))->assertNotFound();
        $this->assertNotSoftDeleted($entry);
        $this->assertSame(1118.0, (float) $bank->fresh()->current_balance);
        $this->assertNotNull($this->posting($entry));
    }

    public function test_unauthorized_and_other_company_users_cannot_delete(): void
    {
        [$bank, , $entry] = $this->context();
        $user = User::factory()->create(['user_type' => 'user', 'current_company_id' => $entry->company_id]);
        $this->actingAs($user)->delete(route('admin.other-transactions.destroy', $entry))->assertForbidden();
        $other = Company::create(['name' => 'Other', 'created_by' => $user->id]);
        $user->update(['user_type' => 'admin', 'current_company_id' => $other->id]);
        $this->withoutMiddleware()->delete(route('admin.other-transactions.destroy', $entry))->assertForbidden();
        $this->assertNotSoftDeleted($entry);
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
    }

    private function approve(OtherTransaction $entry): void
    {
        $this->post(route('admin.other-transactions.approve', $entry))->assertSessionHasNoErrors()->assertRedirect();
    }

    private function posting(OtherTransaction $entry): BankTransaction
    {
        return BankTransaction::where('reference_type', OtherTransaction::class)->where('reference_id', $entry->id)->firstOrFail();
    }

    private function copy(OtherTransaction $entry, string $kind, float $amount): OtherTransaction
    {
        $copy = $entry->replicate();
        $copy->fill(['transaction_no' => $entry->transaction_no.'-NEXT', 'transaction_kind' => $kind,
            'amount' => $amount, 'tax_amount' => 0, 'total_amount' => $amount, 'status' => 'pending_approval',
            'deleted_at' => null])->save();

        return $copy;
    }

    private function context(string $kind = 'income'): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Other Transaction Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $this->actingAs($user);
        $bank = BankAccount::create(['company_id' => $company->id, 'account_code' => 'BANK-1', 'account_name' => 'Bank',
            'account_type' => 'bank', 'opening_balance' => 1000, 'current_balance' => 1000, 'status' => 'active']);
        $ledger = ExpenseLedger::create(['company_id' => $company->id, 'ledger_code' => 'EL-1', 'name' => 'Other Ledger',
            'opening_balance' => 50, 'current_balance' => 50, 'status' => 'active']);
        $this->post(route('admin.other-transactions.store'), ['transaction_kind' => $kind,
            'expense_ledger_id' => $ledger->id, 'bank_account_id' => $bank->id,
            'transaction_date' => '2026-10-22', 'transaction_no' => 'OT-DELETE', 'amount' => 100, 'tax_amount' => 18,
        ])->assertSessionHasNoErrors()->assertRedirect();

        return [$bank, $ledger, OtherTransaction::where('company_id', $company->id)->firstOrFail()];
    }
}
