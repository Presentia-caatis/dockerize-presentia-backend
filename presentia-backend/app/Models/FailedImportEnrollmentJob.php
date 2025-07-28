<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedImportEnrollmentJob extends Model
{
    protected $fillable = [
        'nisn',
        'student_id',
        'school_id',
        'semester_id',
        'class_group_name',
        'row_data',
        'error_message',
    ];
}
