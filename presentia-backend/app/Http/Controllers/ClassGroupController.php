<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\Scopes\SemesterScope;
use App\Sortable;
use Illuminate\Http\Request;

use App\Models\ClassGroup;

use function App\Helpers\current_semester_id;

class ClassGroupController extends Controller
{
    use Filterable, Sortable;
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1',
            'unfilteredSemester' => 'sometimes|boolean'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = ClassGroup::query();

        if ($validatedData['unfilteredSemester'] ?? false) {
            $query->withoutGlobalScope(SemesterScope::class);
        } else {
            $query->withCount([
                'students as students_count' => function ($q) {
                    $q->withoutGlobalScope(SemesterScope::class)
                        ->where('enrollments.semester_id', current_semester_id());
                }
            ]);
        }

        $query = $this->applyFilters($query, $request->input('filter', []), ['school_id']);
        $query = $this->applySort($query, $request->input('sort', []), ['school_id']);

        $data = $query->paginate($perPage);
        

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

        $classGroup = ClassGroup::withoutGlobalScope(SemesterScope::class)
            ->with([
                'students' => function ($q) {
                    $q->withoutGlobalScope(SemesterScope::class)
                        ->withPivot('semester_id');
                }
            ])
            ->findOrFail($id);

        $data = $classGroup->toArray();

        $semesterIds = collect($data['students'])->pluck('pivot.semester_id')->unique()->filter();

        $semesters = \App\Models\Semester::whereIn('id', $semesterIds)->get()->keyBy('id');

        foreach ($data['students'] as &$student) {
            $semesterId = $student['pivot']['semester_id'] ?? null;
            $student['semester'] = $semesterId ? $semesters[$semesterId] ?? null : null;
            unset($student['pivot']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Class group retrieved successfully',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $classGroup = ClassGroup::withoutGlobalScope(SemesterScope::class)->findOrFail($id);
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
        $classGroup = ClassGroup::withoutGlobalScope(SemesterScope::class)->findOrFail($id);
        $classGroup->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Class group deleted successfully'
        ]);
    }
}
