<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\School;

class SchoolController extends Controller
{
    public function index()
    {

        $data = School::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'name' => 'required|string',
            'address' => 'required|string',
            'latest_subscription' => 'required|date',
            'end_subscription' => 'required|date',
            'timezone' => 'required|timezone'
        ]);


        $data = School::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'School created successfully',
            'data' => $data
        ],201);

    }

    public function show(School $School)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'School retrieved successfully',
            'data' => $School
        ]);

    }

    public function update(Request $request, School $School)
    {

        $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'school_name' => 'required|string',
            'address' => 'required|string',
            'latest_subscription' => 'required|date',
            'end_subscription' => 'required|date',
        ]);

        $School->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'School updated successfully',
            'data' => $School
        ]);

    }

    public function destroy(School $School)
    {

        $School->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'School deleted successfully'
        ]);

    }
}
