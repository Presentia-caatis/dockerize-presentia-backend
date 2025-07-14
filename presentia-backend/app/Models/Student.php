<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    use BelongsToSchool, BelongsToSemester;
    protected $fillable = [
        'school_id',
        'class_group_id',
        'is_active',
        'nis',
        'nisn',
        'student_name',
        'gender',
    ];

    public function classGroups()
    {
        return $this->belongsToMany(ClassGroup::class, 'enrollments');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function school() {
        return $this->belongsTo(School::class);
    }

    public function semesters(){
        return $this->belongsToMany(Semester::class, 'enrollments');
    }

    public function enrollments() {
        return $this->hasMany(Enrollment::class);
    }
}
