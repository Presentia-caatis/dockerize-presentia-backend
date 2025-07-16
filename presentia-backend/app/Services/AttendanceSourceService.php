<?php

namespace App\Services;

use App\Models\AttendanceSource;
use App\Models\Scopes\SchoolScope;
use Illuminate\Http\Request;

class AttendanceSourceService {
    public function getAllData(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = AttendanceSource::paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance sources retrieved successfully',
            'data' => $data
        ]);
    }

    public function getData($rawData = false, $abortIfNull = false){
        $attendaceSource = AttendanceSource::first();
        if ($abortIfNull && !$attendaceSource) {
            abort(422,  "Attendance source not found. Please ensure an attendance source is configured.");
        }
        if ($rawData) {
            return $attendaceSource;
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance source retrieved successfully',
            'data' => $attendaceSource
        ]);
    }

    public function getById($id){
        $attendaceSource =AttendanceSource::WithoutGlobalScope(SchoolScope::class)->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance source retrieved successfully',
            'data' => $attendaceSource
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required|string|in:fingerprint,rfid,qr_code,face_recognition',
            'username' => 'required|string',
            'password' => 'required|string',
            'base_url' => 'required|string',
        ]);

        $validatedData['school_id'] = config('school.id');

        $data = AttendanceSource::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance source created successfully',
            'data' => $data
        ]);
    }

    public function update(Request $request,$id)
    {
        $attendanceSource = AttendanceSource::findOrFail($id);

        $validatedData = $request->validate([
            'type' => 'nullable|string|in:fingerprint,rfid,qr_code,face_recognition',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'base_url' => 'nullable|string',
            'token' => 'nullable|string'
        ]);

        $attendanceSource->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance source created successfully',
            'data' =>$attendanceSource
        ]);
    }

    public function destroy($id)
    {
        $attendanceSource = AttendanceSource::findOrFail($id);
        $attendanceSource->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance Source deleted successfully'
        ]);
    }
}