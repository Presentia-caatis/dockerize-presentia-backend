<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbsencePermitType>
 */
class AbsencePermitTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'permit_name' => $this->faker->word,
            'is_active' => $this->faker->boolean,
            'school_id' => \App\Models\School::factory(), 
        ];
    }
}
