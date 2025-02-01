<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SubscriptionFeature;

class SubscriptionFeatureController extends Controller
{
    public function index()
    {

        $data = SubscriptionFeature::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription features retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'feature_id' => 'required|exists:features,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $data = SubscriptionFeature::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription feature created successfully',
            'data' => $data
        ],201);
    }

    public function getById(SubscriptionFeature $subscriptionFeature)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription feature retrieved successfully',
            'data' => $subscriptionFeature
        ]);

    }

    public function update(Request $request, SubscriptionFeature $subscriptionFeature)
    {

        $request->validate([
            'feature_id' => 'required|exists:features,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $subscriptionFeature->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Student updated successfully',
            'data' => $subscriptionFeature
        ]);

    }

    public function destroy(SubscriptionFeature $subscriptionFeature)
    {

        $subscriptionFeature->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription feature deleted successfully'
        ]);

    }
}
