<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\AbsencePermitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AbsencePermitTypeUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_absence_permit_type_has_many_absence_permits()
    {
        $absencePermitType = AbsencePermitType::factory()->create();

        AbsencePermit::factory(3)->create(['absence_permit_type_id' => $absencePermitType->id]);

        $this->assertCount(3, $absencePermitType->absencePermits);
    }
}
