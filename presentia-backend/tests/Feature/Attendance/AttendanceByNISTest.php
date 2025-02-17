<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceByNISTest extends TestCase
{
    use RefreshDatabase;

    public function test_input_attendance_with_valid_NIS()
    {
        $student = Student::factory()->create(['nis' => '12345678']);
        $attendanceWindow = AttendanceWindow::factory()->create(['date' => now()->format('Y-m-d')]);

        $response = $this->postJson('/api/attendance', [
            'nis' => $student->nis,
            'date' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Attendance created successfully',
                 ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_window_id' => $attendanceWindow->id,
        ]);
    }

    public function test_input_attendance_with_invalid_NIS()
    {
        $invalidNIS = '99999999';

        $response = $this->postJson('/api/attendance', [
            'nis' => $invalidNIS,
            'date' => now()->toIso8601String(),
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'Student not found',
                 ]);
    }
}
