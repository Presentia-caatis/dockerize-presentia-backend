<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsencePermitType extends Model
{
    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'school_id',    
        'permit_name',
        'is_active',
    ];

    public function absencePermits()
    {
        return $this->hasMany(AbsencePermit::class);
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'absence_permit_type_schools')
                    ->withTimestamps();
    }
}
