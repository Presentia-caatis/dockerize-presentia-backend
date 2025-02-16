<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Feature;
class FeatureController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = Feature::paginate($perPage);
        return response()->json([
            'status' => 'success',
            'message' => 'Features retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'feature_name' => 'required|string',
            'description' => 'nullable|string',
        ]);


        $data = Feature::create($validatedData);
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
        $validatedData = $request->validate([
            'feature_name' => 'required|string',
            'description' => 'nullable|string',
        ]);


        $feature->update($validatedData);
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
