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
            'subscription_name' => 'Basic',
            'billing_cycle_month' => 1,
            'price' => 0,
        ]);
    }
}
