<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SubscriptionFeature;

class SubscriptionFeatureController extends Controller
{
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = SubscriptionFeature::paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription features retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $data = SubscriptionFeature::create($validatedData);
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

        $validatedData = $request->validate([
            'feature_id' => 'required|exists:features,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $subscriptionFeature->update($validatedData);
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
