<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\AbsencePermit;
class AbsencePermitController extends Controller
{
    public function index()
    {

        $data = AbsencePermit::with('attendance', 'document', 'absencePermitType')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permits retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'document_id' => 'nullable|exists:documents,id',
            'absence_permit_type_id' => 'required|exists:absence_permit_types,id',
            'description' => 'required|string',
        ]);


        $data = AbsencePermit::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit created successfully',
            'data' => $data
        ], 201);

    }

    public function show($id)
    {
        $absencePermit=AbsencePermit::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit retrieved successfully',
            'data' => $absencePermit
        ]);

    }

    public function update(Request $request, $id)
    {
        $absencePermit=AbsencePermit::find($id);
        $validatedData = $request->validate([
            'attendance_id' => 'sometimes|exists:attendances,id',
            'remove_document' => 'sometimes|boolean',
            'document_id' => 'sometimes|nullable|exists:documents,id',
            'absence_permit_type_id' => 'sometimes|exists:absence_permit_types,id',
            'description' => 'sometimes|string',
        ]);
    
        if ($request->boolean('remove_document')) {
            $validatedData['document_id'] = null;
        }
    
        $absencePermit->update($validatedData);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit updated successfully',
            'data' => $absencePermit,
        ]);

    }

    public function destroy($id)
    {
        $absencePermit=AbsencePermit::find($id);
        $absencePermit->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit deleted successfully'
        ]);

    }
}
