<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassGroupSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }
    
    public function run(): void
    {
        ClassGroup::factory()->create([
            'school_id' => $this->school_id,
        ]);

        ClassGroup::factory()->create([
            'school_id' => $this->school_id,
        ]);

        ClassGroup::factory()->create([
            'school_id' => $this->school_id,
        ]);
    }
}
