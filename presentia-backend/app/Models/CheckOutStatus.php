<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CheckOutStatus extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'status_name',
        'description',
        'late_duration',
        'school_id'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'attendance_late_type_schools')
                    ->withTimestamps();
    }
}
