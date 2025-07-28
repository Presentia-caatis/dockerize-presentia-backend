<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\Student;
use App\Sortable;
use Illuminate\Http\Request;

use App\Models\ClassGroup;

class ClassGroupController extends Controller
{
    use Filterable, Sortable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(ClassGroup::query(),  $request->input('filter', []), ['school_id']);
        $query = $this->applySort($query, $request->input('sort' ,[]), ['school_id']);

        $data = $query->withCount('students')->paginate($perPage);

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
        $classGroup = ClassGroup::findOrFail($id);
        $classGroup->load('school');
        return response()->json([
            'status' => 'success',
            'message' => 'Class group retrieved successfully',
            'data' => $classGroup
        ]);
    }

    public function update(Request $request, $id)
    {
        $classGroup = ClassGroup::findOrFail($id);
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
        $classGroup = ClassGroup::findOrFail($id);
        Student::where('class_group_id', $classGroup->id)->update(['class_group_id' => null]);
        $classGroup->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Class group deleted successfully'
        ]);
    }
}
