<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedStoreAttendanceJob extends Model
{
    protected $fillable = [
        'student_id',
        'date',
        'message'
    ];
}
