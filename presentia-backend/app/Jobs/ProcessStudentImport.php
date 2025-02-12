<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\ClassGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ProcessStudentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $students, $existingClassGroups;

    public function __construct($students, $existingClassGroups)
    {
        $this->students = $students;
        $this->existingClassGroups = $existingClassGroups;
    }

    public function handle()
    {
        
        $existingClassGroups = $this->existingClassGroups;
        

        
        foreach ($this->students as $student) {
            if (!isset($existingClassGroups[$student['class_group']])) {
                $classGroup = ClassGroup::create([
                    'school_id' => config('school.id'),
                    'class_name' => $student['class_group'],
                    'amount_of_students' => 0,
                ]);

                $existingClassGroups[$student['class_group']] = $classGroup->id;
            }

            $student['class_group_id'] = $existingClassGroups[$student['class_group']];
            unset($student['class_group']);

            Student::updateOrInsert(
                ['nisn' => $student['nisn']],
                $student
            );
        }
    }
}
