<?php

namespace Tests\Feature;

use App\Models\AttendanceSchedule;
use App\Models\Day;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use PHPUnit\Framework\Attributes\Test;

class AttendanceScheduleTest extends TestCase
{
    use TestCaseHelpers, RefreshDatabase;

    #[Test]
    public function test_user_can_update_attendance_schedule_with_valid_data()
    {
        $schedule = AttendanceSchedule::factory()->create();

        $day = Day::factory()->create([
            'school_id' => $this->authUser->school_id,
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
    public function test_cannot_update_nonexistent_schedule()
    {
        $invalidId = 9999;
        $payload = [
            'name' => 'Updated Name',
            'check_out_end_time' => '13:00:00' 
        ];

        $response = $this->putJson("/api/attendance-schedule/{$invalidId}", $payload);

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'No query results for model [App\Models\AttendanceSchedule] ' . $invalidId
                ]);
    }

    #[Test]
    public function test_cannot_update_without_required_check_out_end_time()
    {
        $schedule = AttendanceSchedule::factory()->create();
        Day::factory()->create([
            'school_id' => $this->authUser->school_id,
            'attendance_schedule_id' => $schedule->id
        ]);

        $payload = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/attendance-schedule/{$schedule->id}", $payload);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['check_out_end_time']);
    }

}
