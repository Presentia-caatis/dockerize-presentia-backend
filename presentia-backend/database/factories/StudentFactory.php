<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // Buat sekolah agar school_id valid
        $school = School::factory()->create();

        return [
            'school_id' => $school->id,
            'class_group_id' => null, // Boleh null
            'is_active' => true,
            'nis' => $this->faker->unique()->numerify('123####'),
            'nisn' => $this->faker->unique()->numerify('456####'),
            'student_name' => $this->faker->name,
            'gender' => $this->faker->randomElement(['male', 'female']),
        ];
    }
}
