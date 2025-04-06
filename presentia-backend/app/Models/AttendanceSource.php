<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSource extends Model
{
    protected $fillable = [
        'name',
        'type',
        'username',
        'password',
        'school_id',
        'get_url_credential_info',
        'post_url_authenticate',
        'post_url_credential_info'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
