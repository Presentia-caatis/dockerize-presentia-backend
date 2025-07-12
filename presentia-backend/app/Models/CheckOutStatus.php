<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckOutStatus extends Model
{
    use BelongsToSchool, BelongsToSemester;
    use HasFactory;

    protected $fillable = [
        'status_name',
        'description',
        'late_duration',
        'school_id',
        'semester_id'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function semester(){
        return $this->belongsTo(Semester::class);
    }
}
