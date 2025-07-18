<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckOutStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\CheckInStatus;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\StudentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\AuthenticatesSchoolStaff;


class StaffStudentAttendanceUnitTest extends TestCase
{
    use AuthenticatesSchoolStaff, WithFaker;
    
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
        
        $checkInStatus = CheckInStatus::factory()->create();
        $checkOutStatus = CheckOutStatus::factory()->create();

        return compact('school', 'classGroup', 'student', 'attendanceWindow', 'checkInStatus', 'checkOutStatus');
    }

    #[Test]
    public function test_user_can_input_manual_attendance_with_valid_data()
    {
        $schoolId = $this->schoolStaffUser->school_id;
    
        $student = Student::factory()->create(['school_id' => $schoolId]);
    
        $attendanceWindow = AttendanceWindow::factory()->create([
            'date' => now()->toDateString(),
            'school_id' => $schoolId,
        ]);
    
        $checkInStatus = CheckInStatus::factory()->create([
            'school_id' => $schoolId,
        ]);
    
        $checkOutStatus = CheckOutStatus::factory()->create([
            'school_id' => $schoolId,
        ]);
    
        $payload = [
            'student_id' => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_status_id' => $checkInStatus->id,
            'check_out_status_id' => $checkOutStatus->id,
            'check_in_time' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'check_out_time' => now()->format('Y-m-d H:i:s')
        ];
    
        $response = $this->postJson('/api/attendance/manual', $payload);
    
        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Attendance created successfully',
                    'data' => [
                        'student_id' => $student->id,
                        'check_in_status_id' => $checkInStatus->id,
                        'check_out_status_id' => $checkOutStatus->id,
                    ]
                ]);
    
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_status_id' => $checkInStatus->id,
            'check_out_status_id' => $checkOutStatus->id,
            'school_id' => $schoolId,
        ]);
    }
    

    public function test_manual_attendance_fails_with_invalid_student_id()
    {
    
        $attendanceWindow = AttendanceWindow::factory()->create();
    
        $payload = [
            'student_id' => 9999,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_time' => now()->format('Y-m-d H:i:s'),
            'check_out_time' => now()->addHour()->format('Y-m-d H:i:s')
        ];
    
        $response = $this->postJson('/api/attendance/manual', $payload);
    
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['student_id']);
    }
    
    public function test_manual_attendance_fails_with_invalid_datetime_format()
    {
        $schoolId = $this->schoolStaffUser->school_id;
    
        $student = Student::factory()->create(['school_id' => $schoolId]);
    
        $attendanceWindow = AttendanceWindow::factory()->create([
            'school_id' => $schoolId,
            'date' => now()->toDateString(),
            'check_in_start_time' => '07:00:00',
            'check_in_end_time' => '08:00:00',
            'check_out_start_time' => '14:00:00',
            'check_out_end_time' => '15:00:00',
        ]);

        $payload = [
            'student_id' => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
            'check_in_time' => 'invalid_time',
            'check_out_time' => 'also_invalid'
        ];

        $response = $this->postJson('/api/attendance/manual', $payload);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['check_in_time', 'check_out_time']);
    }

    #[Test]
    public function it_can_retrieve_today_attendance_list()
    {
        $data = $this->createTestData();
        
        Attendance::factory()->create([
            'student_id' => $data['student']->id,
            'attendance_window_id' => $data['attendanceWindow']->id,
            'check_in_status_id' => $data['checkInStatus']->id,
            'school_id' => $data['school']->id
        ]);
        
        $today = Carbon::today()->toDateString();

        $response = $this->getJson('/api/attendance?school_id=' . $data['school']->id . '&startDate=' . $today . '&endDate=' . $today);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Attendances retrieved successfully',
                    'data' => [
                        'current_page' => 1,
                        'data' => [
                            [
                                'attendance_window' => [
                                    'date' => $today, 
                                ],
                                'student' => [
                                    'student_name' => $data['student']->student_name, 
                                ]
                            ]
                        ],
                    ],
                ]);
    }


}
