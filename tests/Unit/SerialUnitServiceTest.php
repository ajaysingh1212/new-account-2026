<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\SerialUnitService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerialUnitServiceTest extends TestCase
{
    #[Test]
    public function it_auto_selects_only_available_gps_units_with_vts_numbers(): void
    {
        $pool = [
            ['key'=>'sold','serial_no'=>'S-1','vts_sim'=>'V-1','sold'=>true],
            ['key'=>'no-vts','serial_no'=>'S-2','vts_sim'=>'','sold'=>false],
            ['key'=>'valid-1','serial_no'=>'S-3','vts_sim'=>'V-3','sold'=>false],
            ['key'=>'valid-2','serial_no'=>'S-4','vts_sim'=>'V-4','sold'=>false],
        ];

        $units = app(SerialUnitService::class)->reconcile([], $pool, 2, true);

        $this->assertSame(['valid-1','valid-2'], collect($units)->pluck('key')->all());
    }

    #[Test]
    public function it_detects_gps_anywhere_in_the_item_identity(): void
    {
        $item = new Item(['name'=>'Vehicle Controller','description'=>'Inbuilt GPS tracking']);

        $this->assertTrue(app(SerialUnitService::class)->isGpsItem($item));
    }
}
