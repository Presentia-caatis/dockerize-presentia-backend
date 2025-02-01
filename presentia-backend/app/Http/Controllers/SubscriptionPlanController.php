<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    public function index()
    {

        $data = SubscriptionPlan::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plans retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'subscription_name' => 'required|string',
            'billing_cycle_month' => 'required|int',
            'price' => 'required|integer',
        ]);

        $data = SubscriptionPlan::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plan created successfully',
            'data' => $data
        ],201);

    }

    public function getById(SubscriptionPlan $subscriptionPlan)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plan retrieved successfully',
            'data' => $subscriptionPlan
        ]);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $request->validate([
            'subscription_name' => 'required|string',
            'billing_cycle_month' => 'required|int',
            'price' => 'required|integer',
        ]);
        
        $subscriptionPlan->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plan updated successfully',
            'data' => $subscriptionPlan
        ]);
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {

        $subscriptionPlan->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plan deleted successfully'
        ]);

    }
}
