<?php

namespace Database\Factories;

use App\Models\AttendanceWindow;
use App\Models\CheckInStatus;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'school_id' => School::factory(),
            'student_id' => function (array $attributes) {
                return Student::factory()->create(['school_id' => $attributes['school_id']])->id;
            },
            'check_in_status_id' => function (array $attributes) {
                return CheckInStatus::factory()->create(['school_id' => $attributes['school_id']])->id;
            },
            'attendance_window_id' => function (array $attributes) {
                return AttendanceWindow::factory()->create(['school_id' => $attributes['school_id']])->id;
            },
            'check_in_time' => $this->faker->dateTime,
            'check_out_time' => $this->faker->dateTime,
        ];
    }
}
