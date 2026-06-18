<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Sales\SalesInvoiceController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class SalesInvoiceUnitSelectionTest extends TestCase
{
    #[DataProvider('selectionCases')]
    public function test_it_reconciles_serial_units_with_the_changed_quantity(
        array $submitted,
        int $quantity,
        bool $requiresGps,
        array $expectedKeys
    ): void {
        $pool = [
            ['key' => 'A', 'serial_no' => '100', 'vts_sim' => 'SIM-A', 'sold' => false],
            ['key' => 'B', 'serial_no' => '200', 'vts_sim' => 'SIM-B', 'sold' => false],
            ['key' => 'C', 'serial_no' => '300', 'vts_sim' => null, 'sold' => false],
            ['key' => 'D', 'serial_no' => '400', 'vts_sim' => 'SIM-D', 'sold' => true],
        ];

        $method = new ReflectionMethod(SalesInvoiceController::class, 'reconcileSelectedUnits');
        $result = $method->invoke(new SalesInvoiceController(), $submitted, $pool, $quantity, $requiresGps);

        $this->assertSame($expectedKeys, array_column($result, 'key'));
    }

    public static function selectionCases(): array
    {
        return [
            'quantity increase auto-fills available units' => [
                [['key' => 'B', 'serial_no' => '200']],
                2,
                false,
                ['B', 'A'],
            ],
            'quantity decrease keeps only requested count' => [
                [
                    ['key' => 'B', 'serial_no' => '200'],
                    ['key' => 'A', 'serial_no' => '100'],
                ],
                1,
                false,
                ['B'],
            ],
            'stale key remaps by serial number' => [
                [['key' => 'OLD-B', 'serial_no' => '200']],
                1,
                false,
                ['B'],
            ],
            'gps selection skips units without sim' => [
                [],
                3,
                true,
                ['A', 'B'],
            ],
            'sold submitted unit is replaced' => [
                [['key' => 'D', 'serial_no' => '400']],
                1,
                false,
                ['A'],
            ],
        ];
    }
}
