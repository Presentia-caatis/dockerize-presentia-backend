<?php

namespace App\Models;

use App\BelongsToSchool;
use App\BelongsToSemester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsencePermit extends Model
{
    use HasFactory;
    use BelongsToSchool, BelongsToSemester;

    protected $fillable = [
        'school_id',
        'document_id',
        'absence_permit_type_id',
        'description',
        'semester_id'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function absencePermitType()
    {
        return $this->belongsTo(AbsencePermitType::class);
    }

    public function semester(){
        return $this->belongsTo(Semester::class);
    }
}
