<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
use App\Models\CheckOutStatus;
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


class StaffAttendanceStatisticUnitTest extends TestCase
{
    use AuthenticatesSchoolStaff, WithFaker;

    // Active/Inactive Student
    public function test_static_statistic_endpoint_returns_expected_data()
    {
        $school = School::factory()->create();
        config(['school.id' => $school->id]);

        $plan = SubscriptionPlan::factory()->create();
        $school->subscriptionPlan()->associate($plan);
        $school->update(['latest_subscription' => now()]);
        $school->save();

        $this->schoolStaffUser->update(['school_id' => $school->id]);

        Student::factory()->count(3)->create(['is_active' => true, 'gender' => 'male', 'school_id' => $school->id]);
        Student::factory()->count(2)->create(['is_active' => false, 'gender' => 'female', 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'active_students',
                    'inactive_students',
                    'male_students',
                    'female_students',
                    'subscription_packet',
                    'is_subscription_packet_active',
                ]
            ]);
    }

    public function test_daily_statistic_with_no_attendance_window()
    {

        $school = School::factory()->create();
        config(['school.id' => $school->id]);
        $this->schoolStaffUser->update(['school_id' => $school->id]);

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);
        CheckInStatus::factory()->count(3)->create();

        $response = $this->getJson('/api/dashboard-statistic/daily?summarize=1');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'present' => 0,
                'absent' => 5
            ]);
    }

    // 
    public function test_daily_statistic_with_attendance_window_and_attendance()
    {

        $school = School::factory()->create();
        config(['school.id' => $school->id]);
        $this->schoolStaffUser->update(['school_id' => $school->id]);

        $statuses = CheckInStatus::factory()->sequence(
            ['late_duration' => -1, 'status_name' => 'Absent'],
            ['late_duration' => 0, 'status_name' => 'On Time'],
            ['late_duration' => 5, 'status_name' => 'Late']
        )->count(3)->create([
            'school_id' => $school->id
        ]);

        $checkoutNormal = CheckOutStatus::factory()->create([
            'school_id' => $school->id,
            'late_duration' => 0, // Contoh: On Time Checkout
            'status_name' => 'On Time Checkout'
        ]);

        $checkoutAbsent = CheckOutStatus::factory()->create([
            'school_id' => $school->id,
            'late_duration' => -1, // Penting: Status absen untuk checkout
            'status_name' => 'Absent Checkout'
        ]);

        $students = Student::factory()->count(3)->create(['is_active' => true, 'school_id' => $school->id]);

        $date = now()->format('Y-m-d');
        $window = AttendanceWindow::factory()->create(['date' => $date, 'school_id' => $school->id]);

        Attendance::factory()->create([
            'student_id' => $students[0]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[1]->id, // On Time
            'check_out_status_id' => $checkoutNormal->id,
            'school_id' => $school->id
        ]);

        Attendance::factory()->create([
            'student_id' => $students[1]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[0]->id, // Absent
            'check_out_status_id' => $checkoutAbsent->id,
            'school_id' => $school->id
        ]);

        $response = $this->getJson('/api/dashboard-statistic/daily?summarize=1');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'present' => 1,
                'absent' => 1
            ]);
    }

    public function test_daily_statistic_with_summarize_false()
    {

        $school = School::factory()->create();
        config(['school.id' => $school->id]);
        $this->schoolStaffUser->update(['school_id' => $school->id]);

        $statuses = CheckInStatus::factory()->sequence(
            ['late_duration' => -1, 'status_name' => 'Absent'],
            ['late_duration' => 0, 'status_name' => 'On Time']
        )->count(2)->create([
            'school_id' => $school->id
        ]);

        $checkoutOnTime = CheckOutStatus::factory()->create([
            'school_id' => $school->id,
            'late_duration' => 0,
            'status_name' => 'On Time Checkout' 
        ]);

        $checkoutAbsent = CheckOutStatus::factory()->create([
            'school_id' => $school->id,
            'late_duration' => -1,
            'status_name' => 'Absent Checkout' 
        ]);

        $students = Student::factory()->count(2)->create(['is_active' => true, 'school_id' => $school->id]);

        $date = now()->format('Y-m-d');
        $window = AttendanceWindow::factory()->create(['date' => $date, 'school_id' => $school->id]);

        Attendance::factory()->create([
            'student_id' => $students[0]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[0]->id,
            'check_out_status_id' => $checkoutAbsent->id,
            'school_id' => $school->id
        ]);

        Attendance::factory()->create([
            'student_id' => $students[1]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[1]->id,
            'check_out_status_id' => $checkoutOnTime->id,
            'school_id' => $school->id
        ]);

        $response = $this->getJson('/api/dashboard-statistic/daily?summarize=0');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [ 
                '*' => [
                    'attendance_window_name',
                    'attendance_window_type',
                    'statistic' => [
                        'check_in' => [
                            'Total Hadir', 
                            'Absent',     
                            'On Time'     
                        ],
                        'check_out' => [
                            'Total Keluar',
                            'On Time Checkout', 
                            'Absent Checkout'  
                        ]
                    ]
                ]
            ]
            ]);
    }

}
