<?php

namespace App\Services;

use App\Models\Enrollment;

class EnrollmentService
{
    public function getAll(array $data)
    {
        $perPage = $data['perPage'] ?? 10;
        return Enrollment::with(['student', 'classGroup', 'semester'])->paginate($perPage);
    }

    public function getById($id)
    {
        // Use with() before findOrFail()
        return Enrollment::with(['student', 'classGroup', 'semester'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $enrollment = Enrollment::create($data);
        // Use load() to eager load relations
        return $enrollment->load(['student', 'classGroup', 'semester']);
    }

    public function update($id, array $data)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update($data);
        return $enrollment->load(['student', 'classGroup', 'semester']);
    }

    public function delete($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return true;
    }
}