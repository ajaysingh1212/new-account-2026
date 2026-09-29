<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class DashboardDateRangeTest extends TestCase
{
    public function test_custom_dashboard_dates_are_used_inclusively(): void
    {
        $request = Request::create('/admin/dashboard', 'GET', [
            'period' => 'custom',
            'from_date' => '2026-05-04',
            'to_date' => '2026-05-19',
        ]);

        $method = new ReflectionMethod(DashboardController::class, 'dateRange');
        $range = $method->invoke(new DashboardController(), $request);

        $this->assertSame(['custom', '2026-05-04', '2026-05-19'], $range);
    }

    public function test_reversed_custom_dashboard_dates_are_normalized(): void
    {
        $request = Request::create('/admin/dashboard', 'GET', [
            'period' => 'custom',
            'from_date' => '2026-05-19',
            'to_date' => '2026-05-04',
        ]);

        $method = new ReflectionMethod(DashboardController::class, 'dateRange');
        $range = $method->invoke(new DashboardController(), $request);

        $this->assertSame(['custom', '2026-05-04', '2026-05-19'], $range);
    }

    public function test_this_year_uses_calendar_year_to_today(): void
    {
        Carbon::setTestNow('2026-09-29 10:00:00');
        $request = Request::create('/admin/dashboard', 'GET', ['period' => 'this_year']);
        $method = new ReflectionMethod(DashboardController::class, 'dateRange');

        $this->assertSame(['this_year', '2026-01-01', '2026-09-29'], $method->invoke(new DashboardController(), $request));
        Carbon::setTestNow();
    }
}
