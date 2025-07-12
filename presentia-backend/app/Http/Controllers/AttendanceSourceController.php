<?php

namespace App\Http\Controllers;


use App\Services\AttendanceSourceService;
use Illuminate\Http\Request;

class AttendanceSourceController extends Controller
{
    protected AttendanceSourceService $attendanceSourceService;

    public function __construct(AttendanceSourceService $attendanceSourceService)
    {
        $this->attendanceSourceService = $attendanceSourceService;
    }

    public function getAll(Request $request)
    {
        return $this->attendanceSourceService->getAllData($request);
    }

    public function getData()
    {
        return $this->attendanceSourceService->getData();
    }

    public function getById($id)
    {
        return $this->attendanceSourceService->getById($id);
    }

    public function store(Request $request)
    {
        return $this->attendanceSourceService->store($request);
    }

    public function update(Request $request,$id)
    {
        return $this->attendanceSourceService->update($request,$id);
    }

    public function destroy($id)
    {
        return $this->attendanceSourceService->destroy($id);
    }

    
}
