<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceWindow extends Model
{

    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'day_id',
        'name',
        'total_present',
        'total_absent',
        'school_id',
        'type',
        'date',
        'check_in_start_time',
        'check_in_end_time',
        'check_out_start_time',
        'check_out_end_time'
    ];

    public function school() {
        return $this->belongsTo(School::class);
    }
}
