<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\Scopes\SchoolScope;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }

    public function run(): void
    {
    
        Student::factory(60)->create([
            'school_id' => $this->school_id,
        ]);
    }
}
