<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
            'school_name' => $this->faker->company,
            'address' => $this->faker->address,
            'latest_subscription' => $this->faker->dateTimeThisYear,
            'end_subscription' => $this->faker->dateTimeThisYear,
        ];
    }
}
