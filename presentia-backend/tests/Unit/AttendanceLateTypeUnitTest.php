<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\AttendanceLateType;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AttendanceLateTypeUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_attendance_late_type_has_many_attendances()
    {
        $lateType = AttendanceLateType::factory()->create();
        Attendance::factory(3)->create(['attendance_late_type_id' => $lateType->id]);

        $this->assertCount(3, $lateType->attendances);
    }

    #[Test]
    public function an_attendance_late_type_belongs_to_many_schools()
    {
        $lateType = AttendanceLateType::factory()->create();
        $schools = School::factory(3)->create();

        $lateType->schools()->attach($schools);

        $this->assertCount(3, $lateType->schools);
    }

    #[Test]
    public function it_can_create_an_attendance_late_type_record()
    {
        $lateType = AttendanceLateType::create([
            'type_name' => 'Late',
            'description' => 'Arrived late to school',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('attendance_late_types', [
            'id' => $lateType->id,
            'type_name' => 'Late',
            'description' => 'Arrived late to school',
            'is_active' => true,
        ]);
    }
}
