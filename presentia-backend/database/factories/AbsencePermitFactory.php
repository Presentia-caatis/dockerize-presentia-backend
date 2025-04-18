<?php

namespace Database\Factories;

use App\Models\AbsencePermitType;
use App\Models\Document;
use App\Models\School;
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
            'school_id' => School::factory(),
            'document_id' => Document::factory(),
            'absence_permit_type_id' => AbsencePermitType::factory(),
            'description' => $this->faker->sentence,
        ];
    }
}
