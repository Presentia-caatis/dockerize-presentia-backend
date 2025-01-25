<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_name',
        'description',
    ];

    public function subscriptionFeatures()
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    public function schoolFeatures()
    {
        return $this->hasMany(SchoolFeature::class);
    }
}
