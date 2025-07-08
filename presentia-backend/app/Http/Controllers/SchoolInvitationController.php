<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\SchoolInvitation;
use App\Sortable;
use Illuminate\Http\Request;

class SchoolInvitationController extends Controller
{
    use Filterable, Sortable;
    public function index(Request $request){
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $query = SchoolInvitation::query();
        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        $data = $query->with('sender', 'receiver', 'school', '')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'School invitations retrieved successfully',
            'data' => $data
        ]);
    }
}
