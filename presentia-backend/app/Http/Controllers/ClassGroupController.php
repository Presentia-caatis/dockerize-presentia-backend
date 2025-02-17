<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ClassGroup;

class ClassGroupController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = ClassGroup::withCount('students')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Class groups retrieved successfully',
            'data' => $data
        ]);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_name' => 'required|string'
        ]);

        $data = ClassGroup::create($validatedData);
        $data->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group created successfully',
            'data' => $data
        ], 201);
    }

    public function getById($id)
    {
        $classGroup = ClassGroup::find($id);
        $classGroup->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group retrieved successfully',
            'data' => $classGroup
        ]);
    }

    public function update(Request $request, $id)
    {
        $classGroup = ClassGroup::find($id);
        $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_name' => 'required|string'
        ]);

        $classGroup->update($validatedData);
        $classGroup->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group updated successfully',
            'data' => $classGroup
        ]);
    }

    public function destroy($id)
    {
        $classGroup = ClassGroup::find($id);
        $classGroup->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Class group deleted successfully'
        ]);
    }
}
