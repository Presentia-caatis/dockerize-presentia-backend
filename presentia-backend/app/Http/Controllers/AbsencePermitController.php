<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\Attendance;
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
            'attendance_ids' => 'nullable|array|min:1',
            'attendance_ids.*' => 'integer|distinct|exists:attendances,id',
            'document_id' => 'nullable|exists:documents,id',
            'absence_permit_type_id' => 'required|exists:absence_permit_types,id',
            'description' => 'required|string',
        ]);
        
        $validatedData['school_id'] = current_school_id();
        
        $absencePermit = AbsencePermit::create($validatedData);

        isset($validatedData['attendance_ids']) && Attendance::whereIn('id', $validatedData['attendance_ids'])
            ->update(['absence_permit_id' => $absencePermit->id]);

        $absencePermit->load('attendances');
        return response()->json([
            'status' => 'success',
            'message' => 'Absence permit created successfully',
            'data' => $absencePermit
        ], 201);

    }

    public function getById($id)
    {
        $absencePermit=AbsencePermit::with('document')->findOrFail($id);

        $absencePermit->document->path = asset('storage/' . $absencePermit->document->path);
        
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
            'attendance_ids' => 'nullable|array|min:1',
            'attendance_ids.*' => 'integer|distinct|exists:attendances,id',
            'remove_document' => 'boolean',
            'document_id' => 'nullable|exists:documents,id',
            'absence_permit_type_id' => 'nullable|exists:absence_permit_types,id',
            'description' => 'nullable|string',
        ]);
        
        if ($request->boolean('remove_document')) {
            $validatedData['document_id'] = null;
        }


        if (isset($validatedData['attendance_ids'])) {
            $newIds = collect($validatedData['attendance_ids']);
            $currentIds = $absencePermit->attendances->pluck('id');
    
            $toDetach = $currentIds->diff($newIds);
            $toAttach = $newIds->diff($currentIds);
    
            if ($toDetach->isNotEmpty()) {
                Attendance::whereIn('id', $toDetach)->update(['absence_permit_id' => null]);
            }
    
            if ($toAttach->isNotEmpty()) {
                Attendance::whereIn('id', $toAttach)->update(['absence_permit_id' => $absencePermit->id]);
            }
        }

        $absencePermit->update($validatedData);
        $absencePermit->load('attendances');

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
