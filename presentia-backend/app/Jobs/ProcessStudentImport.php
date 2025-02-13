<?php

namespace App\Jobs;

use App\Models\Scopes\SchoolScope;
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

    protected $students, $schoolId, $method;

    public function __construct($students, $schoolId, $method)
    {
        $this->students = $students;
        $this->schoolId = $schoolId;
        $this->method = $method;
    }

    public function handle()
{
    $existingClassGroups = ClassGroup::withoutGlobalScope(SchoolScope::class)
        ->where('school_id', $this->schoolId)
        ->pluck('id', 'class_name') // ['class_name' => id]
        ->toArray();

    foreach ($this->students as $student) {
        try {
            // Ensure the class group exists
            if (!isset($existingClassGroups[$student['class_group_name']])) {
                $classGroup = ClassGroup::create([
                    'school_id' => $student['school_id'],
                    'class_name' => $student['class_group_name'],
                    'amount_of_students' => 0,
                ]);
                $existingClassGroups[$student['class_group_name']] = $classGroup->id;
            }
            $student['class_group_id'] = $existingClassGroups[$student['class_group_name']];
            unset($student['class_group_name']);

            if ($this->method == "put") {
                $updateStudent = Student::withoutGlobalScope(SchoolScope::class)
                    ->where('nisn', $student['nisn'])
                    ->where('school_id', $this->schoolId)
                    ->first();
                
                if ($updateStudent) {
                    $updateStudent->update($student);
                } else {
                    Student::create($student);
                }
            } else {
                Student::insert($student); 
            }
        } catch (\Exception $e) {
            continue;
        }
    }
}

}
