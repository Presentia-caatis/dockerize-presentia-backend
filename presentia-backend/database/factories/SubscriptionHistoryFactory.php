<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionHistory>
 */
class SubscriptionHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'school_id' => \App\Models\School::factory(),
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
        ];
    }
}
