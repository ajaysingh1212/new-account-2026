<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReplacementAndAgeingRoutesTest extends TestCase
{
    public function test_replacement_and_ageing_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.replacements.index'));
        $this->assertTrue(Route::has('admin.replacements.create'));
        $this->assertTrue(Route::has('admin.replacements.lookup'));
        $this->assertTrue(Route::has('admin.estimates.convert-form'));
        $this->assertTrue(Route::has('admin.delivery-challans.convert'));
        $this->assertTrue(Route::has('admin.reports.ageing.party-print'));
        $this->assertTrue(Route::has('admin.reports.ageing.party-diagnosis'));
        $this->assertTrue(Route::has('admin.reports.ageing.print'));
        $this->assertTrue(Route::has('admin.reports.ageing.diagnosis'));
    }
}
