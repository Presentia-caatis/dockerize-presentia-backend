<?php

namespace Database\Seeders;

use App\Models\AbsencePermitType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AbsencePermitTypeSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }

    public function run(): void
    {
        AbsencePermitType::create([
            'permit_name' => 'Alpha',
            'is_active' => true,
            'school_id' => $this->school_id
        ]);

        AbsencePermitType::create([
            'permit_name' => 'Dispensation',
            'is_active' => true,
            'school_id' => $this->school_id
        ]);

        AbsencePermitType::create([
            'permit_name' => 'Sick',
            'is_active' => true,
            'school_id' => $this->school_id
        ]);
    }
}
