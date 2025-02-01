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
        'name',
        'type',
        'date',
        'check_in_start_time',
        'check_in_end_time',
        'check_out_start_time',
        'check_out_end_time'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'days')->withTimestamps();
    }
}
