<?php

namespace Tests\Feature;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\CheckOutStatus;
use App\Models\Day;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\CheckInStatus;
use App\Models\AttendanceLateType;
use App\Models\School;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;
use Tests\Traits\AuthenticatesSchoolAdmin;


class SchoolAdminAttendanceManagementUnitTest extends TestCase
{
    use AuthenticatesSchoolAdmin;

    private function createTestData()
    {
        $school = School::find($this->schoolAdminUser->school_id);
        
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id
        ]);
        
        $attendanceWindow = AttendanceWindow::factory()->create([
            'school_id' => $school->id,
            'date' => now()->format('Y-m-d')
        ]);
        
        $checkInStatus = CheckInStatus::factory()->create();
        $checkOutStatus = CheckOutStatus::factory()->create();

        return compact('school', 'classGroup', 'student', 'attendanceWindow', 'checkInStatus', 'checkOutStatus');
    }

    #[Test]
    public function school_admin_can_retrieve_attendance_list()
    {
        $data = $this->createTestData();
        
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->getJson('/api/attendance');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    
    #[Test]
    public function school_admin_can_filter_attendance_by_date_range()
    {
        $data = $this->createTestData();
        
        $todayWindow = AttendanceWindow::factory()->create([
            'date' => now()->format('Y-m-d'),
            'school_id' => $data['school']->id
        ]);
        
        $yesterdayWindow = AttendanceWindow::factory()->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'school_id' => $data['school']->id
        ]);

        // Presensi hari ini
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $todayWindow->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        // Presensi kemarin 
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $yesterdayWindow->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->getJson('/api/attendance?startDate='.now()->format('Y-m-d').'&endDate='.now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.attendance_window_id', $todayWindow->id);
    }

    #[Test]
    public function school_admin_can_filter_attendance_by_class()
    {
        $data = $this->createTestData();
        
        $otherClass = ClassGroup::factory()->create(['school_id' => $data['school']->id]);
        $otherStudent = Student::factory()->create([
            'school_id' => $data['school']->id,
            'class_group_id' => $otherClass->id
        ]);

        // Presensi kelas yang difilter
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        // Presensi kelas lain
        Attendance::factory()->create([
            'student_id' => $otherStudent->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->getJson('/api/attendance?classGroup='.$data['classGroup']->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.student_id', $data['student']->id);
    }

    #[Test]
    public function school_admin_can_filter_attendance_by_status()
    {
        $data = $this->createTestData();
        $otherStatus = CheckInStatus::factory()->create();

        $window1 = AttendanceWindow::factory()->create([
            'school_id' => $data['school']->id,
            'date' => now()->format('Y-m-d')
        ]);
        
        $window2 = AttendanceWindow::factory()->create([
            'school_id' => $data['school']->id,
            'date' => now()->addDay()->format('Y-m-d')
        ]);

        // Presensi dengan status yang difilter
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $window1->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        // Presensi dengan status lain
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $window2->id,
            'check_in_status_id' => $otherStatus->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->getJson('/api/attendance?filter[check_in_status_id]='.$data['checkInStatus']->id);
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.check_in_status_id', $data['checkInStatus']->id);
    }

    #[Test]
    public function school_admin_can_search_attendance_by_keyword()
    {
        $data = $this->createTestData();

        // Presensi yang sesuai pencarian
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->getJson('/api/attendance?search='.$data['student']->student_name);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.student.student_name', $data['student']->student_name);
    }

    #[Test]
    public function school_admin_can_sort_attendance_by_column()
    {
        $data = $this->createTestData();
        
        $studentA = Student::factory()->create([
            'school_id' => $data['school']->id,
            'class_group_id' => $data['classGroup']->id,
            'student_name' => 'Aaa Student'
        ]);
        
        $studentB = Student::factory()->create([
            'school_id' => $data['school']->id,
            'class_group_id' => $data['classGroup']->id,
            'student_name' => 'Bbb Student'
        ]);

        $windowA = AttendanceWindow::factory()->create([
            'school_id' => $data['school']->id,
            'date' => now()->format('Y-m-d')
        ]);
        
        $windowB = AttendanceWindow::factory()->create([
            'school_id' => $data['school']->id,
            'date' => now()->addDay()->format('Y-m-d')
        ]);

        Attendance::factory()->create([
            'student_id' => $studentB->id,
            'attendance_window_id' => $windowB->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id,
            'check_in_time' => now()->addDay()->format('Y-m-d H:i:s')
        ]);

        Attendance::factory()->create([
            'student_id' => $studentA->id,
            'attendance_window_id' => $windowA->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id,
            'check_in_time' => now()->format('Y-m-d H:i:s')
        ]);

        // Urutkan ascending
        $response = $this->getJson('/api/attendance?sort[student.student_name]=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.student.student_name', 'Aaa Student')
            ->assertJsonPath('data.data.1.student.student_name', 'Bbb Student');
    }

    #[Test]
    public function school_admin_can_export_attendance_to_csv()
    {
        $data = $this->createTestData();
        
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $response = $this->get('/api/attendance/export?startDate='.now()->subWeek()->format('Y-m-d').'&endDate='.now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    #[Test]
    public function school_admin_can_update_attendance_schedule_with_valid_data()
    {
        $schedule = AttendanceSchedule::factory()->create();

        $day = Day::factory()->create([
            'school_id' => $this->schoolAdminUser->school_id,
            'attendance_schedule_id' => $schedule->id
        ]);

        $payload = [
            'name' => 'Updated Schedule Name',
            'check_in_start_time' => '07:00:00',
            'check_in_end_time' => '08:00:00',
            'check_out_start_time' => '12:00:00',
            'check_out_end_time' => '13:00:00',
        ];

        $response = $this->putJson("/api/attendance-schedule/{$schedule->id}", $payload);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Attendance schedule updated successfully',
                    'data' => [
                        'id' => $schedule->id,
                        'name' => 'Updated Schedule Name',
                        'check_in_start_time' => '07:00:00',
                        'check_in_end_time' => '08:00:00',
                        'check_out_start_time' => '12:00:00',
                        'check_out_end_time' => '13:00:00',
                    ]
                ]);
    }

    #[Test]
    public function school_admin_cannot_update_without_required_check_out_end_time()
    {
        $schedule = AttendanceSchedule::factory()->create();
        Day::factory()->create([
            'school_id' => $this->schoolAdminUser->school_id,
            'attendance_schedule_id' => $schedule->id
        ]);

        $payload = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/attendance-schedule/{$schedule->id}", $payload);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['check_out_end_time']);
    }

    #[Test]
    public function school_admin_can_update_attendance()
    {
        $data = $this->createTestData();
        
        $attendance = Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id,
            'check_in_time' => now()->subHour()->format('Y-m-d H:i:s')
        ]);

        $updateData = [
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_time' => now()->format('Y-m-d H:i:s'),
            'check_in_status_id' => $data['checkInStatus']->id,
            'check_out_time' => now()->addHours(5)->format('Y-m-d H:i:s'),
            'check_out_status_id' => $data['checkOutStatus']->id
        ];

        $response = $this->putJson("/api/attendance/{$attendance->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Attendance updated successfully'
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_in_time' => now()->format('Y-m-d H:i:s'),
            'check_out_time' => now()->addHours(5)->format('Y-m-d H:i:s')
        ]);
    }

    #[Test]
    public function school_admin_cannot_update_attendance_with_invalid_data()
    {
        $data = $this->createTestData();
        
        $attendance = Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);

        $invalidData = [
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_time' => 'invalid-date-format',
            'check_in_status_id' => $data['checkInStatus']->id
        ];

        $response = $this->putJson("/api/attendance/{$attendance->id}", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in_time']);
    }

}
