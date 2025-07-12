<?php

namespace App\Services;

use App\Models\Enrollment;

class EnrollmentService
{
    public function getAll()
    {
        return Enrollment::all();
    }

    public function getById($id)
    {
        return Enrollment::findOrFail($id);
    }

    public function create(array $data)
    {
        return Enrollment::create($data);
    }

    public function update($id, array $data)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update($data);
        return $enrollment;
    }

    public function delete($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return true;
    }
}
