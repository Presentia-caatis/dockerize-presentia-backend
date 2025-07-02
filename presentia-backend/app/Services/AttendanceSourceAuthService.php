<?php

namespace App\Services;

use App\Models\AttendanceSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AttendanceSourceAuthService
{
    protected AttendanceSourceService $attendanceSourceService;

    protected $attendanceSource;

    public function __construct(AttendanceSourceService $attendanceSourceService)
    {
        $this->attendanceSourceService = $attendanceSourceService;
        $this->attendanceSource = $this->attendanceSourceService->getData(true, true);
    }

    public function login(Request $request)
    {
        if (auth()->user()->hasAnyPermission(['manage_students']) && !($request->manualLogin ?? false)) {
            $request->merge([
                'username' => $this->attendanceSource['username'],
                'password' => $this->attendanceSource['password'],
            ]);
        }

        $response = Http::post("{$this->attendanceSource['base_url']}/api/login", [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        $responseBody = $response->json();

        if ($response->status() == 200) {
            $token = $responseBody["data"]["token"];
            $updateRequest = new Request([
                'token' => $token,
            ]);

            if ($request->changeCredential ?? false) {
                $updateRequest->merge([
                    'username' => $request->username,
                    'password' => $request->password,
                ]);
            }

            $this->attendanceSourceService->update($updateRequest, $this->attendanceSource->id);
            $this->attendanceSource = $this->attendanceSourceService->getData(true, true);

            return response()->json([
                'status' => 'success',
                'message' => $responseBody["message"],
                'token' => $token
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $response->json('message'),
        ], $response->status());
    }

    public function checkValidToken($secondAttempt = false)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->attendanceSource->token ? 'Bearer ' . $this->attendanceSource->token : null,
        ])->get("{$this->attendanceSource->base_url}/api/user");;

        if ($response->status() != 200) {

            if ($secondAttempt) {
                abort(422, "Invalid credential, please check and update the credential in your attendance source data, then try again.");
            }

            if (auth()->user()->hasAnyPermission(['manage_students'])) {
                $this->login(new Request());
                $this->checkValidToken(true);
                return;
            }

            abort(422, $response->json()["message"] == "No token provided" ?
                "Expired or invalid token. Please log in again to the attendance source service."
                : $response->json()["message"]);
        }
    }
}