<?php

namespace App\Http\Controllers;

use App\Filterable;
use Illuminate\Http\Request;

use App\Models\AbsencePermit;
use Illuminate\Validation\ValidationException;
use function App\Helpers\current_school_id;
class AbsencePermitController extends Controller
{
    use Filterable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(AbsencePermit::query(),  $request->input('filter', []), ['school_id']);

        $data = $query->with('attendances', 'document', 'absencePermitType')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Absence permits retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'document_id' => 'nullable|exists:documents,id',
            'absence_permit_type_id' => 'required|exists:absence_permit_types,id',
            'description' => 'required|string',
        ]);

        $validatedData['school_id'] = current_school_id();
        
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
        $validatedData = $request->validate([
            'remove_document' => 'boolean',
            'document_id' => 'exists:documents,id',
            'absence_permit_type_id' => 'exists:absence_permit_types,id',
            'description' => 'string',
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
