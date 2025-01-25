<?php

namespace Database\Factories;

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
            'student_id' => \App\Models\Student::factory(),
            'attendance_late_type_id' => \App\Models\AttendanceLateType::factory(),
            'check_in_time' => $this->faker->dateTime,
            'check_out_time' => $this->faker->dateTime,
        ];
    }
}
