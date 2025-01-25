<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Attendance;
use App\Models\AttendanceLateType;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AttendanceUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_attendance_belongs_to_a_student()
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(Student::class, $attendance->student);
    }

    #[Test]
    public function an_attendance_belongs_to_an_attendance_late_type()
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(AttendanceLateType::class, $attendance->attendanceLateType);
    }

    #[Test]
    public function an_attendance_has_many_absence_permits()
    {
        $attendance = Attendance::factory()->create();
        AbsencePermit::factory(3)->create(['attendance_id' => $attendance->id]);

        $this->assertCount(3, $attendance->absencePermits);
    }

    #[Test]
    public function it_can_create_an_attendance_record()
    {
        $student = Student::factory()->create();
        $lateType = AttendanceLateType::factory()->create();

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'attendance_late_type_id' => $lateType->id,
            'check_in_time' => now(),
            'check_out_time' => now()->addHours(8),
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $student->id,
            'attendance_late_type_id' => $lateType->id,
        ]);
    }
}
