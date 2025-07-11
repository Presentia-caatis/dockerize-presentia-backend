<?php

namespace Tests\Feature;

use App\Models\Attendance;      
use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;  
use App\Models\CheckOutStatus;  
use App\Models\ClassGroup;
use App\Models\Student;         
use App\Models\School;          
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestCaseHelpers; 
use Carbon\Carbon;
use Tests\Traits\AuthenticatesSchoolStaff; 

class StaffStudentAndAttendanceManagementTest extends TestCase
{
    use WithFaker, AuthenticatesSchoolStaff;

    private function createTestData()
    {
        $school = School::find($this->schoolStaffUser->school_id);
        
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id
        ]);
        
        $attendanceWindow = AttendanceWindow::factory()->create([
            'school_id' => $school->id,
            'date' => now()->format('Y-m-d')
        ]);
        
        $checkInStatus = CheckInStatus::factory()->create([
                'school_id' => $school->id,
                'late_duration' => 0,
                "is_active" => true
            ]);
        
        $checkInStatus2 = CheckInStatus::factory()->create([
                'school_id' => $school->id,
                'late_duration' => -1,
                "is_active" => true
            ]);

        $checkOutStatus = CheckOutStatus::factory()->create([
                'school_id' => $school->id,
                'late_duration' => 0,
                "is_active" => true
            ]);

        $checkOutStatus2 = CheckOutStatus::factory()->create([
                'school_id' => $school->id,
                'late_duration' => -1,
                "is_active" => true
            ]);

        return compact('school', 'classGroup', 'student', 'attendanceWindow', 'checkInStatus', 'checkOutStatus');
    }


    #[Test]
    public function student_attendance_management(): void
    {
        // --- 0. Initial Setup ---
        $schoolId = $this->schoolStaffUser->school_id;

        $data = $this->createTestData();
        $school = $data['school'];
        $student = $data['student'];
        $attendanceWindow = $data['attendanceWindow'];
        $checkInStatus = $data['checkInStatus'];
        $checkOutStatus = $data['checkOutStatus'];

        // --- 1. Input Presensi (Manual) ---
        $today = Carbon::today(); 
        $manualAttendancePayload = [
            'student_id'           => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_status_id'   => $checkInStatus->id,
            'check_out_status_id'  => $checkOutStatus->id,
            'check_in_time'        => $today->setHour(7)->setMinute(15)->setSecond(0)->format('Y-m-d H:i:s'),
            'check_out_time'       => $today->setHour(12)->setMinute(0)->setSecond(0)->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/attendance/manual', $manualAttendancePayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Attendance created successfully',
                     'data'    => [
                         'student_id'          => $student->id,
                         'attendance_window_id' => $attendanceWindow->id,
                         'check_in_status_id'  => $checkInStatus->id,
                         'check_out_status_id' => $checkOutStatus->id,
                         'school_id'           => $schoolId,
                     ],
                 ]);

        $this->assertDatabaseHas('attendances', [
            'student_id'           => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_status_id'   => $checkInStatus->id,
            'check_out_status_id'  => $checkOutStatus->id,
            'school_id'            => $schoolId,
        ]);


        // --- 2. Tampilkan Presensi ---
        $response = $this->getJson('/api/attendance?school_id='. $schoolId. '&startDate=' . $today->format('Y-m-d') . '&endDate=' . $today->format('Y-m-d'));

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Attendances retrieved successfully',
                 ])
                 ->assertJsonFragment([
                     'student_id'           => $student->id,
                     'attendance_window_id' => $attendanceWindow->id,
                     'check_in_status_id'   => $checkInStatus->id,
                 ]);

        // --- 3. Export Presensi ---
        Storage::fake('public'); 

        $response = $this->get('/api/attendance/export?startDate=' . $today->format('Y-m-d') . '&endDate=' . $today->format('Y-m-d') . '&classGroup=' . $student->class_group_id);

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
