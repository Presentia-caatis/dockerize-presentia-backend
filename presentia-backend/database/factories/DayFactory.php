<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceSchedule;
use App\Models\School;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Day>
 */
class DayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_schedule_id' => AttendanceSchedule::factory(),
            'school_id' => School::factory(),
            'name' => $this->faker->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
        ];
    }
}
