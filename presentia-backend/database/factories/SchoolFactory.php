<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'latest_subscription' => $this->faker->dateTimeThisYear,
            'school_token' => Str::random(10),
        ];
    }
}
