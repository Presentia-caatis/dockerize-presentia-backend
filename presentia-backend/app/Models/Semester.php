<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use BelongsToSchool;

    protected  $fillable = [
        'school_id',
        'academic_year',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    

}
