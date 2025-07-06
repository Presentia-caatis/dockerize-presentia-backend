<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AttendanceSource extends Model
{
    use BelongsToSchool;
    protected $fillable = [
        'type',
        'username',
        'password',
        'token',
        'school_id',
        'base_url',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
