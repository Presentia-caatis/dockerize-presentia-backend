<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SchoolFeature;

class SchoolFeatureController extends Controller
{
    public function index()
    {

        $data = SchoolFeature::all();
        return response()->json([
            'status' => 'success',
            'message' => 'School features retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'feature_id' => 'required|exists:features,id',
            'status' => 'required|boolean',
        ]);


        $data = SchoolFeature::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'School feature created successfully',
            'data' => $data
        ],201);

    }

    public function getById(SchoolFeature $schoolFeature)
    {

        return response()->json([
            'status' => 'success',
            'message' => 'School feature retrieved successfully',
            'data' => $schoolFeature
        ]);

    }

    public function update(Request $request, SchoolFeature $schoolFeature)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'feature_id' => 'required|exists:features,id',
            'status' => 'required|boolean',
        ]);

        $schoolFeature->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'School feature updated successfully',
            'data' => $schoolFeature
        ]);

    }

    public function destroy(SchoolFeature $schoolFeature)
    {

        $schoolFeature->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'School feature deleted successfully'
        ]);

    }
}
