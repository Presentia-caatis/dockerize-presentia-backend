<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_name',
        'billing_cycle_month',
        'price',
    ];

    public function subscriptionFeatures()
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    public function schoolUsers()
    {
        return $this->hasMany(School::class);
    }

    public function subscriptionHistories()
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
