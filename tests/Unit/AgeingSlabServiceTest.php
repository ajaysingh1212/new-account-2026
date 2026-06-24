<?php

namespace Tests\Unit;

use App\Services\AgeingSlabService;
use PHPUnit\Framework\TestCase;

class AgeingSlabServiceTest extends TestCase
{
    public function test_it_groups_every_party_on_one_row_with_the_new_ageing_slabs(): void
    {
        $service = new AgeingSlabService();
        $rows = collect([
            ['party_id' => 1, 'party' => 'Alpha', 'kind' => 'receivable', 'invoice' => 'S-1', 'age' => 20, 'due' => 100],
            ['party_id' => 1, 'party' => 'Alpha', 'kind' => 'receivable', 'invoice' => 'S-2', 'age' => 45, 'due' => 200],
            ['party_id' => 1, 'party' => 'Alpha', 'kind' => 'payable', 'invoice' => 'P-1', 'age' => 151, 'due' => 300],
        ]);

        $matrix = $service->matrix($rows);

        $this->assertCount(1, $matrix);
        $this->assertSame(1, $matrix[0]['slabs']['1_30']['bills']);
        $this->assertSame(1, $matrix[0]['slabs']['31_60']['bills']);
        $this->assertSame(1, $matrix[0]['slabs']['150_plus']['bills']);
        $this->assertSame(600.0, $matrix[0]['total_due']);
    }
}
