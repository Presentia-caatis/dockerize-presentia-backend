<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\AbsencePermitType;

class AbsencePermitTypeController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = AbsencePermitType::paginate($perPage);
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
            'school_id' => 'required|exists:schools,id',
        ]);


        $data = AbsencePermitType::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type created successfully',
            'data' => $data
        ], 201);
    }

    public function getById($id)
    {
        $absencePermitType=AbsencePermitType::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type retrieved successfully',
            'data' => $absencePermitType
        ]);
    }

    public function update(Request $request, $id)
    {
        $absencePermitType=AbsencePermitType::find($id);
        $validatedData = $request->validate([
            'permit_name' => 'required|string',
            'is_active' => 'required|boolean',
            'school_id' => 'required|exists:schools,id',
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
        $absencePermitType=AbsencePermitType::find($id);
        $absencePermitType->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit type deleted successfully'
        ]);

    }
}
