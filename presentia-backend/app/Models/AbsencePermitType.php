<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsencePermitType extends Model
{
    use HasFactory;
    use BelongsToSchool, BelongsToSemester;

    protected $fillable = [
        'school_id',    
        'permit_name',
        'is_active',
        'semester_id'
    ];

    public function semester(){
        return $this->belongsTo(Semester::class);
    }

    public function absencePermits()
    {
        return $this->hasMany(AbsencePermit::class);
    }
}
