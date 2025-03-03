<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\Student;
use App\Models\School;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use Carbon\Carbon;
use Tests\TestCaseHelpers;

class AttendanceStatisticDashboardTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    #[Test]
    public function it_can_display_student_attendance_count()
    {
        $this->setUpTestData();

        $response = $this->getJson(route('api/dashboard-statistic/daily'));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message', 'data' => ['present', 'absent']
                 ]);
    }

    #[Test]
    public function it_can_display_student_absence_count()
    {
        $this->setUpTestData(0); 

        $response = $this->getJson(route('api/dashboard-statistic/daily'));

        $response->assertStatus(200)
                 ->assertJsonFragment(['absent' => 5]); 
    }

    #[Test]
    public function it_can_display_student_attendance_statistics()
    {
        $this->setUpTestData();

        $response = $this->getJson(route('api/dashboard-statistic/daily', ['summarize' => false]));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message', 'data' => ['Total Hadir']
                 ]);
    }

    #[Test]
    public function it_can_display_student_activity_status()
    {
        School::factory()->create();
        Student::factory()->count(3)->create(['is_active' => true]);
        Student::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson(route('api/dashboard-statistic/static'));

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'active_students' => 3,
                     'inactive_students' => 2,
                 ]);
    }

    private function setUpTestData($presentCount = 3)
    {
        $school = School::factory()->create();
        $students = Student::factory()->count(5)->create();
        
        $attendanceWindow = AttendanceWindow::factory()->create([
            'date' => Carbon::today(),
            'school_id' => $school->id,
            'day_id' => 1,
            'name' => 'Attendance Window',
            'type' => 'default',
            'check_in_start_time' => '08:00:00',
            'check_in_end_time' => '09:00:00',
            'check_out_start_time' => '15:00:00',
            'check_out_end_time' => '16:00:00'
        ]);

        $checkInStatus = CheckInStatus::factory()->create();

        Attendance::factory()->count($presentCount)->create([
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_status_id' => $checkInStatus->id,
            'school_id' => $school->id
        ]);
    }
}
