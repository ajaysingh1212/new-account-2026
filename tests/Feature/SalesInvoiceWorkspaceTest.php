<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_defaults_to_current_month_and_keeps_other_months_searchable(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Invoice Workspace', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        foreach (['2026-10-15', '2025-10-15', '2026-09-15'] as $date) {
            SalesInvoice::create([
                'company_id' => $company->id,
                'sale_type' => 'cash',
                'invoice_no' => 'SI-'.$date,
                'billing_date' => $date,
                'grand_total' => 100,
                'status' => 'posted',
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('admin.sales.index'))->assertOk();
        $response->assertSee('id="salesMonth" class="form-control" value="2026-10"', false);
        foreach (['2026-10', '2025-10', '2026-09'] as $month) {
            $response->assertSee('data-month="'.$month.'"', false);
        }
        $response->assertSee('salesSeeMore')->assertSee('js/search-highlight.js');
        $this->assertSame(3, substr_count($response->getContent(), 'class="btn btn-outline-primary btn-sm sale-row-toggle"'));
        $response->assertSee('sale-cell-preview')->assertSee('sale-row-expanded');
        $this->travelBack();
    }
}
