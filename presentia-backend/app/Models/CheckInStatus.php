<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInStatus extends Model
{
    use BelongsToSchool;
    use HasFactory;
    
    protected $fillable = [
        'status_name',
        'description',
        'late_duration',
        'is_active',
        'school_id'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
