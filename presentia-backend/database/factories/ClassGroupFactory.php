<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'school_id' =>  \App\Models\School::factory(), 
            'class_name' => $this->faker->word,
            'amount_of_students' => $this->faker->numberBetween(10, 30),
        ];
    }
}
