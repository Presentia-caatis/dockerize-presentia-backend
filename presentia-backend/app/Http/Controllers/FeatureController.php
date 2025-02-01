<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Feature;
class FeatureController extends Controller
{
    public function index()
    {

        $data = Feature::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Features retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'feature_name' => 'required|string',
            'description' => 'nullable|string',
        ]);


        $data = Feature::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Feature created successfully',
            'data' => $data
        ],201);

    }

    public function getById(Feature $feature)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'Feature retrieved successfully',
            'data' => $feature
        ]);

    }

    public function update(Request $request, Feature $feature)
    {
        $request->validate([
            'feature_name' => 'required|string',
            'description' => 'nullable|string',
        ]);


        $feature->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Feature updated successfully',
            'data' => $feature
        ]);

    }

    public function destroy(Feature $feature)
    {

        $feature->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Feature deleted successfully'
        ]);

    }
}
