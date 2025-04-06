<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedAdjustAttendanceJob extends Model
{
    protected $fillable = [
        'student_id',
        'attendance_window_id',
        'upcoming_attendance_window_data',
        'context',
        'message'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function attendanceWindow()
    {
        return $this->belongsTo(AttendanceWindow::class);
    }
}
