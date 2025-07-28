<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;
    use BelongsToSchool;
    use BelongsToSemester;

    protected $fillable = [
        'attendance_schedule_id',
        'school_id',
        'name',
        'semester_id'
    ];


    public function attendanceSchedule()
    {
        return $this->belongsTo(AttendanceSchedule::class);
    }


}
