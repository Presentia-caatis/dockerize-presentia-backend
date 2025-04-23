<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceWindow>
 */
class AttendanceWindowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'day_id' => \App\Models\Day::factory(),
            'name' => $this->faker->word,
            'date' => $this->faker->date,
            'type' => $this->faker->randomElement(['default', 'event', 'holiday']),
            'check_in_start_time' => $this->faker->time,
            'check_in_end_time' => $this->faker->time,
            'check_out_start_time' => $this->faker->time,
            'check_out_end_time' => $this->faker->time,
        ];
    }
}
