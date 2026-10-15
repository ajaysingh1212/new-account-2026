<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\EntryVisibility;
use App\Models\Expense;
use App\Models\ExpenseLedger;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_approved_expense_reverses_bank_and_ledger_and_preserves_other_postings(): void
    {
        [$user, $company, $bank, $ledger, $expense] = $this->context();
        $this->get(route('admin.expenses.index'))->assertOk()->assertSee(route('admin.expenses.destroy', $expense));
        $this->get(route('admin.expenses.show', $expense))->assertOk()->assertSee('Delete');
        $this->post(route('admin.expenses.approve', $expense))->assertRedirect();
        $this->assertSame(882.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(168.0, (float) $ledger->fresh()->current_balance);
        $posting = BankTransaction::where('reference_type', Expense::class)->where('reference_id', $expense->id)->firstOrFail();
        $later = BankTransaction::create([
            'company_id' => $company->id, 'bank_account_id' => $bank->id, 'transaction_date' => '2026-10-15',
            'transaction_type' => 'manual_adjustment', 'direction' => 'in', 'amount' => 23,
            'balance_after' => 905, 'reference_no' => 'UNRELATED', 'created_by' => $user->id,
        ]);
        $bank->update(['current_balance' => 905]);

        $this->delete(route('admin.expenses.destroy', $expense))->assertSessionHasNoErrors()->assertRedirect(route('admin.expenses.index'));
        $this->assertSoftDeleted($expense);
        $this->assertSame(1023.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
        $this->assertDatabaseMissing('bank_transactions', ['id' => $posting->id]);
        $this->assertDatabaseMissing('entry_visibilities', ['entry_type' => BankTransaction::class, 'entry_id' => $posting->id]);
        $this->assertDatabaseMissing('entry_visibilities', ['entry_type' => Expense::class, 'entry_id' => $expense->id]);
        $this->assertSame(23.0, (float) $later->fresh()->amount);
        $this->assertSame(1023.0, (float) $later->fresh()->balance_after);
        $audit = AuditLog::where('action', 'deleted')->where('model', Expense::class)->where('model_id', $expense->id)->firstOrFail();
        $this->assertSame($posting->id, $audit->old_values['bank_postings'][0]['id']);
        $this->get(route('admin.expenses.index'))->assertOk()->assertDontSee($expense->expense_no);
        $this->get(route('admin.expenses.show', $expense))->assertNotFound();
        $this->delete(route('admin.expenses.destroy', $expense))->assertNotFound();
        $this->post(route('admin.expenses.approve', $expense))->assertNotFound();
        $this->assertSame(1023.0, (float) $bank->fresh()->current_balance);
    }

    public function test_pending_and_rejected_expenses_can_be_deleted_without_changing_balances(): void
    {
        [, , $bank, $ledger, $expense] = $this->context();
        $this->delete(route('admin.expenses.destroy', $expense))->assertRedirect();
        $this->assertSoftDeleted($expense);
        $rejected = $expense->replicate();
        $rejected->fill(['expense_no' => 'EXP-REJECTED', 'status' => 'rejected', 'deleted_at' => null])->save();
        $this->delete(route('admin.expenses.destroy', $rejected))->assertRedirect();
        $this->assertSoftDeleted($rejected);
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
        $this->assertDatabaseCount('bank_transactions', 0);
    }

    public function test_multiple_expenses_on_the_same_bank_and_ledger_are_reversed_individually(): void
    {
        [, $company, $bank, $ledger, $first] = $this->context();
        $second = $first->replicate();
        $second->fill(['expense_no' => 'EXP-SECOND', 'amount' => 50, 'tax_amount' => 0, 'total_amount' => 50])->save();
        $this->post(route('admin.expenses.approve', $first))->assertRedirect();
        $this->post(route('admin.expenses.approve', $second))->assertRedirect();
        $this->post(route('admin.expenses.approve', $second))->assertUnprocessable();
        $this->assertSame(832.0, (float) $bank->fresh()->current_balance);
        $this->delete(route('admin.expenses.destroy', $first))->assertRedirect();
        $this->assertSame(950.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(100.0, (float) $ledger->fresh()->current_balance);
        $remaining = BankTransaction::where('reference_type', Expense::class)->where('reference_id', $second->id)->firstOrFail();
        $this->assertSame(950.0, (float) $remaining->balance_after);
        $this->delete(route('admin.expenses.destroy', $second))->assertRedirect();
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
    }

    public function test_unauthorized_and_other_company_users_cannot_delete_an_expense(): void
    {
        [, $company, $bank, $ledger, $expense] = $this->context();
        $user = User::factory()->create(['user_type' => 'user', 'current_company_id' => $company->id]);
        $role = Role::create(['company_id' => $company->id, 'name' => 'Expense Viewer', 'slug' => 'expense-viewer', 'is_active' => true]);
        UserRole::create(['company_id' => $company->id, 'user_id' => $user->id, 'role_id' => $role->id]);
        $view = Permission::firstOrCreate(['slug' => 'expenses.view'], ['name' => 'View Expenses', 'module' => 'expenses']);
        $delete = Permission::firstOrCreate(['slug' => 'expenses.delete'], ['name' => 'Delete Expenses', 'module' => 'expenses']);
        $role->permissions()->attach($view);
        EntryVisibility::where('entry_type', Expense::class)->where('entry_id', $expense->id)->update(['visible_to_all_company' => true]);
        $this->actingAs($user)->delete(route('admin.expenses.destroy', $expense))->assertForbidden();
        $this->get(route('admin.expenses.show', $expense))->assertOk()->assertDontSee('Delete expense and reverse its postings');
        $role->permissions()->attach($delete);
        // Delete permission alone does not allow deleting another user's shared entry.
        $this->delete(route('admin.expenses.destroy', $expense))->assertForbidden();
        $other = Company::create(['name' => 'Other Company', 'created_by' => $user->id]);
        $user->update(['user_type' => 'admin', 'current_company_id' => $other->id]);
        $this->withoutMiddleware()->delete(route('admin.expenses.destroy', $expense))->assertForbidden();
        $this->assertNotSoftDeleted($expense);
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
    }

    public function test_deletion_rolls_back_all_effects_when_a_required_ledger_is_missing(): void
    {
        [, , $bank, $ledger, $expense] = $this->context();
        $this->post(route('admin.expenses.approve', $expense))->assertRedirect();
        $other = Company::create(['name' => 'Other ledger company', 'created_by' => auth()->id()]);
        $ledger->update(['company_id' => $other->id]);
        $this->delete(route('admin.expenses.destroy', $expense))->assertNotFound();
        $this->assertNotSoftDeleted($expense);
        $this->assertSame(882.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(168.0, (float) $ledger->fresh()->current_balance);
        $this->assertDatabaseHas('bank_transactions', ['reference_type' => Expense::class, 'reference_id' => $expense->id, 'amount' => 118]);
    }

    public function test_all_legacy_duplicate_approval_postings_are_reversed(): void
    {
        [, , $bank, $ledger, $expense] = $this->context();
        $this->post(route('admin.expenses.approve', $expense))->assertRedirect();
        $posting = BankTransaction::where('reference_type', Expense::class)->where('reference_id', $expense->id)->firstOrFail();
        $copy = $posting->replicate();
        $copy->balance_after = 764;
        $copy->save();
        $bank->update(['current_balance' => 764]);
        $ledger->update(['current_balance' => 286]);

        $this->delete(route('admin.expenses.destroy', $expense))->assertRedirect();
        $this->assertSame(1000.0, (float) $bank->fresh()->current_balance);
        $this->assertSame(50.0, (float) $ledger->fresh()->current_balance);
        $this->assertDatabaseCount('bank_transactions', 0);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Expense Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $this->actingAs($user);
        $bank = BankAccount::create([
            'company_id' => $company->id, 'account_code' => 'BANK-1', 'account_name' => 'Business Bank',
            'account_type' => 'bank', 'opening_balance' => 1000, 'current_balance' => 1000, 'status' => 'active',
        ]);
        $ledger = ExpenseLedger::create([
            'company_id' => $company->id, 'ledger_code' => 'EL-1', 'name' => 'Office Expenses',
            'opening_balance' => 50, 'current_balance' => 50, 'status' => 'active',
        ]);
        $this->post(route('admin.expenses.store'), [
            'expense_ledger_id' => $ledger->id, 'bank_account_id' => $bank->id,
            'expense_date' => '2026-10-15', 'expense_no' => 'EXP-DELETE-TEST', 'vendor_name' => 'Office Vendor',
            'amount' => 100, 'tax_amount' => 18, 'visible_to_all_company' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.expenses.index'));

        return [$user, $company, $bank, $ledger, Expense::firstOrFail()];
    }
}
