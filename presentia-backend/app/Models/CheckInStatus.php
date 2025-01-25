<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInStatus extends Model
{
    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'type_name',
        'description',
        'is_active',
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
