<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use BelongsToSchool, BelongsToSemester;
    protected $fillable = [
        'semester_id',
        'class_group_id',
        'student_id',
        'school_id',
    ];

    // Relationships

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
