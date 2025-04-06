<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSchedule extends Model
{
    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'event_id',
        'type',
        'name',
        'check_in_start_time',
        'check_in_end_time',
        'check_out_start_time',
        'check_out_end_time'
    ];

    public function days() 
    {
        return $this->hasMany(Day::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'days');
    }
}
