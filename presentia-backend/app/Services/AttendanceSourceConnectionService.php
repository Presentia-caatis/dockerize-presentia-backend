<?php

namespace App\Services;

use App\Models\AttendanceSource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceSourceConnectionService
{
    protected AttendanceSourceService $attendanceSourceService;

    protected $attendanceSource;

    public function __construct(AttendanceSourceService $attendanceSourceService)
    {
        (new AttendanceSourceAuthService($attendanceSourceService))->checkValidToken();
        $this->attendanceSourceService = $attendanceSourceService;
        $this->attendanceSource = $this->attendanceSourceService->getData(true, true);
    }

    protected function client()
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->attendanceSource->token ? 'Bearer ' . $this->attendanceSource->token : null,
        ]);
    }

    public function enroll(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'finger_id' => 'required|string',
            'retry' => 'required|integer|min:0',
            'machine_number' => 'required|string',
            'overwrite' => 'required|boolean'
        ]);

        $response = $this->client()->post("{$this->attendanceSource->base_url}/adms/command?include={$request->machine_number}", [
            'header' => ['ENROLL_FP'],
            'body' => [
                'PIN' => $request->student_id,
                'FID' => $request->finger_id,
                'RETRY' => $request->retry,
                'OVERWRITE' => $request->overwrite ? '1' : '0',
            ],
        ]);

        if ($response->status() == 200) {
            return response()->json([
                'status' => 'success',
                'message' => 'enrollment request sent successfully.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $response->json('message'),
        ], $response->status());
    }

    public function getAllData(Request $request)
    {
        $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $response = $this->client()->get("{$this->attendanceSource->base_url}/adms/fingerprint");

        $data = $response->json();
        $mp_data = [];

        if (!empty($data['error'])) {
            return response()->json([
                'status' => 'error',
                'message' => $response->json('message'),
            ], $response->status());
        }

        foreach ($data["data"] as $item) {
            $mp_data[$item['pin']][] = $item['data']['FID'];
        }

        $paginated = Student::with("classGroup")->paginate($perPage);

        $paginated->getCollection()->transform(function ($student) use ($mp_data) {
            $studentId = $student->id;

            $studentArray = $student->toArray();

            $studentArray['has_credential'] = isset($mp_data[$studentId]);
            $studentArray['credential_ids'] = $mp_data[$studentId] ?? [];
            return $studentArray;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $paginated
        ], 200);
    }

    public function updateAuthProfile(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $response = $this->client()->put("{$this->attendanceSource->base_url}/api/user", [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        if ($response->status() == 200) {
            return response()->json([
                'status' => 'success',
                'message' => 'Attendance source profile (third party) updated successfully.',
                "data" => $response->json("data")
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $response->json('message'),
        ], $response->status());
    }
}