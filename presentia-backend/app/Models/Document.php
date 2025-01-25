<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//SCHOOL DATA MODEL
class Document extends Model
{
    use HasFactory;
    use BelongsToSchool;
    protected $fillable = [
        'school_id', 
        'document_name',
        'path',
    ];

    public function absencePermits()
    {
        return $this->hasMany(AbsencePermit::class);
    }
}
