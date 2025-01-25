<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsencePermit extends Model
{
    use HasFactory;
    use BelongsToSchool;

    protected $fillable = [
        'attendance_id',
        'document_id',
        'absence_permit_type_id',
        'description',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function absencePermitType()
    {
        return $this->belongsTo(AbsencePermitType::class);
    }
}
