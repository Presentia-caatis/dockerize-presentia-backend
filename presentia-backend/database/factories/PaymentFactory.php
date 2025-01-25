<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
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
            'payment_date' => $this->faker->dateTime,
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
            'amount' => $this->faker->numberBetween(10, 100),
            'status' => $this->faker->randomElement(['completed', 'pending', 'failed']),
        ];
    }
}
