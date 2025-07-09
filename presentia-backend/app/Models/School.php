<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'name',
        'address',
        'latest_subscription',
        'end_subscription',
        'timezone',
        'school_token',
        'logo_image_path'
    ];

    public function getLogoImagePathAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }


    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function schoolFeatures()
    {
        return $this->hasMany(SchoolFeature::class);
    }

    public function subscriptionHistories()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function absencePermitTypes()
    {
        return $this->hasMany(AbsencePermitType::class);
    }

    public function attendanceLateTypes()
    {
        return $this->hasMany(CheckInStatus::class);
    }
}
