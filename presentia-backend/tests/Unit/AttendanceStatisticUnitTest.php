<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
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


class AttendanceStatisticUnitTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers, WithFaker;

    public function test_static_statistic_endpoint_returns_expected_data()
    {
        $school = School::factory()->create();
        config(['school.id' => $school->id]);

        $plan = SubscriptionPlan::factory()->create();
        $school->subscriptionPlan()->associate($plan);
        $school->update(['latest_subscription' => now()]);
        $school->save();

        $this->authUser->update(['school_id' => $school->id]);

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
        $this->authUser->update(['school_id' => $school->id]);

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);
        CheckInStatus::factory()->count(3)->create();

        $response = $this->getJson('/api/dashboard-statistic/daily?summarize=1');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'present' => 0,
                'absent' => 5
            ]);
    }

    public function test_daily_statistic_with_attendance_window_and_attendance()
    {

        $school = School::factory()->create();
        config(['school.id' => $school->id]);
        $this->authUser->update(['school_id' => $school->id]);

        $statuses = CheckInStatus::factory()->sequence(
            ['late_duration' => -1, 'status_name' => 'Absent'],
            ['late_duration' => 0, 'status_name' => 'On Time'],
            ['late_duration' => 5, 'status_name' => 'Late']
        )->count(3)->create();

        $students = Student::factory()->count(3)->create(['is_active' => true, 'school_id' => $school->id]);

        $date = now()->format('Y-m-d');
        $window = AttendanceWindow::factory()->create(['date' => $date, 'school_id' => $school->id]);

        Attendance::factory()->create([
            'student_id' => $students[0]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[1]->id, // On Time
            'school_id' => $school->id
        ]);

        Attendance::factory()->create([
            'student_id' => $students[1]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[0]->id, // Absent
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
        $this->authUser->update(['school_id' => $school->id]);

        $statuses = CheckInStatus::factory()->sequence(
            ['late_duration' => -1, 'status_name' => 'Absent'],
            ['late_duration' => 0, 'status_name' => 'On Time']
        )->count(2)->create();

        $students = Student::factory()->count(2)->create(['is_active' => true, 'school_id' => $school->id]);

        $date = now()->format('Y-m-d');
        $window = AttendanceWindow::factory()->create(['date' => $date, 'school_id' => $school->id]);

        Attendance::factory()->create([
            'student_id' => $students[0]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[0]->id,
            'school_id' => $school->id
        ]);

        Attendance::factory()->create([
            'student_id' => $students[1]->id,
            'attendance_window_id' => $window->id,
            'check_in_status_id' => $statuses[1]->id,
            'school_id' => $school->id
        ]);

        $response = $this->getJson('/api/dashboard-statistic/daily?summarize=0');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'Total Hadir',
                    'Absent',
                    'On Time'
                ]
            ]);
    }

}
