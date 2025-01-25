<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionFeature>
 */
class SubscriptionFeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'feature_id' => \App\Models\Feature::factory(),
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
        ];
    }
}
