<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AbsencePermit>
 */
class AbsencePermitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'attendance_id' => \App\Models\Attendance::factory(),
            'document_id' => \App\Models\Document::factory(),
            'absence_permit_type_id' => \App\Models\AbsencePermitType::factory(),
            'description' => $this->faker->sentence,
        ];
    }
}
