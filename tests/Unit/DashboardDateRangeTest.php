<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;
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
}
