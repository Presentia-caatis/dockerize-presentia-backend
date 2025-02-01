<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    use BelongsToSchool;
    protected $fillable = [
        'school_id',
        'class_group_id',
        'is_active',
        'nis',
        'nisn',
        'student_name',
        'gender',
    ];

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

public function school() {
    return $this->belongsTo(School::class);
}
}
