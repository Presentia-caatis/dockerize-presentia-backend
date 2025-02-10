<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        SubscriptionPlan::create([
            'subscription_name' => 'Free',
            'billing_cycle_month' => 0,
            'price' => 0,
        ]);
    }
}
