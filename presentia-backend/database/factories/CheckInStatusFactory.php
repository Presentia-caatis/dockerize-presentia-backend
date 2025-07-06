<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceLateType>
 */
class CheckInStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'status_name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'late_duration' => $this->faker->numberBetween(1, 60), // Durasi keterlambatan dalam menit
            'is_active' => $this->faker->boolean,
        ];
    }
}
