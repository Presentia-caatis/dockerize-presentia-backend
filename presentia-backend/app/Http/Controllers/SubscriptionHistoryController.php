<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SubscriptionHistory;

class SubscriptionHistoryController extends Controller
{
    public function index()
    {

        $data = SubscriptionHistory::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription histories retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
        ]);


        $data = SubscriptionHistory::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription history created successfully',
            'data' => $data
        ],201);

    }

    public function getById(SubscriptionHistory $subscriptionHistory)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription history retrieved successfully',
            'data' => $subscriptionHistory
        ]);

    }

    public function destroy(SubscriptionHistory $subscriptionHistory)
    {

        $subscriptionHistory->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Subscription history deleted successfully'
        ]);

    }
}

