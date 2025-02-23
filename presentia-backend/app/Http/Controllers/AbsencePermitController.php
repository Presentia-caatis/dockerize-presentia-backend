<?php

namespace App\Http\Controllers;

use App\Filterable;
use Illuminate\Http\Request;

use App\Models\AbsencePermit;
class AbsencePermitController extends Controller
{
    use Filterable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AbsencePermit::query(),  $request->input('filter', []), ['school']);

        $data = $query->with('attendance', 'document', 'absencePermitType')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Absence permits retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'document_id' => 'nullable|exists:documents,id',
            'absence_permit_type_id' => 'required|exists:absence_permit_types,id',
            'description' => 'required|string',
        ]);


        $data = AbsencePermit::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit created successfully',
            'data' => $data
        ], 201);

    }

    public function getById($id)
    {
        $absencePermit=AbsencePermit::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit retrieved successfully',
            'data' => $absencePermit
        ]);

    }

    public function update(Request $request, $id)
    {
        $absencePermit=AbsencePermit::findOrFail($id);
        $validatedData = $validatedData = $request->validate([
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
        $absencePermit=AbsencePermit::findOrFail($id);
        $absencePermit->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit deleted successfully'
        ]);

    }
}
