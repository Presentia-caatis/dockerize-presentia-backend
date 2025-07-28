<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\CheckOutStatus;
use Illuminate\Http\Request;

class CheckOutStatusController extends Controller
{
    use Filterable;
    /**
     * Display a listing of the resource.
     */
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(CheckOutStatus::query(),  $request->input('filter', []), ['school_id']);

        $data = $query->orderBy('late_duration')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Check in statuses retrieved successfully',
            'data' => $data
        ]);
    }
    
    /**
     * Display the specified resource.
     */
    public function getById(string $id)
    {
        //
    }
}
