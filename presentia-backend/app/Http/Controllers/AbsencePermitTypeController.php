<?php

namespace App\Http\Controllers;

use App\Filterable;
use Illuminate\Http\Request;

use App\Models\AbsencePermitType;
use Illuminate\Validation\ValidationException;
use function App\Helpers\current_school_id;

class AbsencePermitTypeController extends Controller
{
    use Filterable;
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AbsencePermitType::query(),  $request->input('filter', []), ['school_id']);

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit types retrieved successfully',
            'data' => $data 
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'permit_name' => 'required|string',
            'is_active' => 'required|boolean', 
        ]);

        $validatedData['school_id'] = current_school_id();

        $data = AbsencePermitType::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type created successfully',
            'data' => $data
        ], 201);
    }

    public function getById($id)
    {
        $absencePermitType=AbsencePermitType::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type retrieved successfully',
            'data' => $absencePermitType
        ]);
    }

    public function update(Request $request, $id)
    {
        $absencePermitType=AbsencePermitType::findOrFail($id);
        $validatedData = $request->validate([
            'permit_name' => 'string',
            'is_active' => 'boolean',
        ]);

        $absencePermitType->update($validatedData);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type updated successfully',
            'data' => $absencePermitType
        ]);

    }

    public function destroy($id)
    {
        $absencePermitType=AbsencePermitType::findOrFail($id);
        $absencePermitType->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type deleted successfully'
        ]);

    }
}
