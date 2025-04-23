<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceSchedule>
 */
class AttendanceScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => null,
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['default', 'event', 'holiday']),
            'check_in_start_time' => $this->faker->time(),
            'check_in_end_time' => $this->faker->time(),
            'check_out_start_time' => $this->faker->time(),
            'check_out_end_time' => $this->faker->time(),
        ];
    }
}
