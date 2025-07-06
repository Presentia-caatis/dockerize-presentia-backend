<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CheckOutStatus>
 */
class CheckOutStatusFactory extends Factory
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
            'status_name' => $this->faker->word, 
            'description' => $this->faker->sentence, 
            'late_duration' => $this->faker->numberBetween(1, 120), 
            'created_at' => now(), 
            'updated_at' => now(), 
        ];
    }
}
