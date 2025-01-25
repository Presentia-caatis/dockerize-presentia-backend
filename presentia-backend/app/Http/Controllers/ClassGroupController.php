<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ClassGroup;

class ClassGroupController extends Controller
{
    public function index()
    {

        $data = ClassGroup::get();
        return response()->json([
            'status' => 'success',
            'message' => 'Class groups retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_name' => 'required|string',
            'amount_of_students' => 'required|integer',
        ]);

        $data = ClassGroup::create($request->all());
        $data->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group created successfully',
            'data' => $data
        ],201);

    }

    public function show(ClassGroup $classGroup)
    {
        $classGroup->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group retrieved successfully',
            'data' => $classGroup
        ]);

    }

    public function update(Request $request, ClassGroup $classGroup)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'class_name' => 'required|string',
            'amount_of_students' => 'required|integer',
        ]);

        $classGroup->update($request->all());
        $classGroup->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group updated successfully',
            'data' => $classGroup
        ]);
    }

    public function destroy(ClassGroup $classGroup)
    {

        $classGroup->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Class group deleted successfully'
        ]);

    }
}
