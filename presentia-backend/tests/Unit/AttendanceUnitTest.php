<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\Student;
use App\Models\CheckInStatus;
use App\Models\School;
use Database\Factories\StudentFactory;
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
    
        //dd($attendance->toArray(), $attendance->student);

        $this->assertNotNull($attendance->student); 
        $this->assertInstanceOf(Student::class, $attendance->student);
    }


    #[Test]
    public function an_attendance_belongs_to_a_school()
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(School::class, $attendance->school);
    }

    #[Test]
    public function an_attendance_belongs_to_an_attendance_window()
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(AttendanceWindow::class, $attendance->attendanceWindow);
    }

    #[Test]
    public function an_attendance_belongs_to_a_check_in_status()
    {
        $attendance = Attendance::factory()->create();

        $this->assertInstanceOf(CheckInStatus::class, $attendance->checkInStatus);
    }

    #[Test]
    public function an_attendance_may_belong_to_an_absence_permit()
    {
        $attendanceWithPermit = Attendance::factory()->create([
            'absence_permit_id' => AbsencePermit::factory(),
        ]);

        $this->assertNotNull($attendanceWithPermit->absencePermit);
        $this->assertInstanceOf(AbsencePermit::class, $attendanceWithPermit->absencePermit);
    }

    #[Test]
    public function it_can_create_an_attendance_record()
    {
        $student = Student::factory()->create();

        $checkIn = CheckInStatus::factory()->create();
        
        $attendanceWindow = AttendanceWindow::factory()->create();

        $school = School::factory()->create();
        
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'check_in_time' => now(),
            'check_out_time' => now()->addHours(8),
            'school_id' => $school->id,
            'check_in_status_id' => $checkIn->id,
            'attendance_window_id' => $attendanceWindow->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'student_id' => $student->id,
            'check_in_time' => now(),
            'check_out_time' => now()->addHours(8),
            'school_id' => $school->id,
        ]);
    }

    

}
