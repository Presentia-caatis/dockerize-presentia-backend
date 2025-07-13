<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    use BelongsToSchool, BelongsToSemester;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_name'
    ];
    public function semesters(){
        return $this->belongsToMany(Semester::class, 'enrollments');
    }
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    
}
