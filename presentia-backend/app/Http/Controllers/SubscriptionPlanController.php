<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = SubscriptionPlan::paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plans retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'subscription_name' => 'required|string',
            'billing_cycle_month' => 'required|int',
            'price' => 'required|integer',
        ]);

        $data = SubscriptionPlan::create($validatedData);
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
        $validatedData = $request->validate([
            'subscription_name' => 'required|string',
            'billing_cycle_month' => 'required|int',
            'price' => 'required|integer',
        ]);
        
        $subscriptionPlan->update($validatedData);
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
