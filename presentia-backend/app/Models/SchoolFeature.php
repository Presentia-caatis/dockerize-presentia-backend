<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'feature_id',
        'status',
    ];

    public function School()
    {
        return $this->belongsTo(School::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
