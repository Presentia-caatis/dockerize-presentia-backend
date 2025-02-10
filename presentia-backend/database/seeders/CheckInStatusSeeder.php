<?php

namespace Database\Seeders;

use App\Models\CheckInStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CheckInStatusSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }
    
    public function run(): void
    {
        CheckInStatus::create([
            'status_name' => 'Retarted',
            'description' => 'Stop Being Fockin Retorded',
            'late_duration' => 30,
            'is_active' => true,
            'school_id' => $this->school_id,
        ]);

        CheckInStatus::create([
            'status_name' => 'Late',
            'description' => 'Late',
            'late_duration' => 15,
            'is_active' => true,
            'school_id' => $this->school_id,
        ]);

        CheckInStatus::create([
            'status_name' => 'On Time',
            'description' => 'On Time',
            'late_duration' => 0,
            'is_active' => true,
            'school_id' => $this->school_id,
        ]);

        CheckInStatus::create([
            'status_name' => 'Absence',
            'description' => 'Absence',
            'late_duration' => -1,
            'is_active' => true,
            'school_id' => $this->school_id,
        ]);
    }
}
