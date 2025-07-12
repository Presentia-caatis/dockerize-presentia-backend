<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'class_name'
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments');
    
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function semesters(){
        return $this->belongsToMany(Semester::class, 'enrollments');
    }
}
