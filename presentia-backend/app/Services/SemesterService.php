<?php

namespace App\Services;

use App\Filterable;
use App\Models\Semester;
use App\Sortable;
use Illuminate\Validation\ValidationException;

class SemesterService
{
    use Filterable, Sortable;
    public function getAll(array $data)
    {
        $perPage = $data['perPage'] ?? 10;
        return Semester::paginate($perPage);
    }

    public function getById($id)
    {
        return Semester::findOrFail($id);
    }

    public function create(array $data)
    {
        $this->checkDateOverlap($data['start_date'], $data['end_date']);

        return Semester::create($data);
    }

    public function update($id, array $data)
    {
        $semester = Semester::findOrFail($id);

        $startDate = $data['start_date'] ?? $semester->start_date;
        $endDate = $data['end_date'] ?? $semester->end_date;

        $this->checkDateOverlap($startDate, $endDate, $semester->id);

        $semester->update($data);

        return $semester;
    }

    public function delete($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->delete();
        return true;
    }

    protected function checkDateOverlap($startDate, $endDate, $ignoreId = null)
    {
        $query = Semester::
            where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date_range' => 'Semester date range overlaps with another semester in this school.'
            ]);
        }
    }
}