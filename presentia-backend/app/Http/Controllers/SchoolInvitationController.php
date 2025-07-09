<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\SchoolInvitation;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use App\Sortable;
use Illuminate\Http\Request;

class SchoolInvitationController extends Controller
{
    use Filterable, Sortable;
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $query = SchoolInvitation::query();
        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        $data = $query->with('sender', 'receiver', 'roleToAssign')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'School invitations retrieved successfully',
            'data' => $data
        ]);
    }

    public function getBySender(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = SchoolInvitation::where('sender_id', auth()->user()->id)->with('roleToAssign:name', 'receiver')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'School invitations retrieved successfully',
            'data' => $data
        ]);
    }

    public function getByReceiver(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = SchoolInvitation::withoutGlobalScope(SchoolScope::class)->where('receiver_id', auth()->user()->id)->with('roleToAssign:id,name', 'sender', 'school')
            ->paginate($perPage);

        if ($data->school->logo_image_path) {
            $data->school->logo_image_path = asset('storage/' . $data->school->logo_image_path);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'School invitations retrieved successfully',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'role_to_assign_id' => 'required|exists:roles,id',
        ]);

        $validatedData["sender_id"] = auth()->user()->id;

        if (auth()->user()->hasRole('super_admin')) {
            $request->validate([
                'school_id' => 'required|exists:schools,id',
            ]);
            $validatedData["school_id"] = $request->school_id;
        } else {
            $validatedData["school_id"] = $validatedData["sender_id"]?->school_id;
        }

        $this->checkDuplicateInvitation($validatedData["receiver_id"], $validatedData["school_id"]);

        $invitation = SchoolInvitation::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'School invitation sent successfully',
            'data' => $invitation
        ]);
    }

    public function respondInvitation(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        $invitation = SchoolInvitation::findOrFail($id);

        if ($validatedData['status'] == 'accepted') {
            $receiver = User::findOrFail($invitation->receiver_id);

            $receiver->syncRoles([$invitation->roleToAssign->name]);
            $receiver->school_id = $invitation->school_id;
            $receiver->save();
        }

        $invitation->status = $validatedData['status'];
        $invitation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'School invitation responded successfully',
            'data' => $invitation
        ]);
    }


    public function checkDuplicateInvitation($reciever_id, $school_id)
    {
        $invitation = SchoolInvitation::where('receiver_id', $reciever_id)
            ->where('school_id', $school_id)
            ->where('status', "pending")
            ->first();

        if ($invitation) {
            return response()->json([
                'status' => 'error',
                'message' => 'The request conflicts with an existing invitation for this user and school.'
            ], 409);
        }
    }

    public function update(Request $request, $id)
    {
        $invitation = SchoolInvitation::findOrFail($id);

        $validatedData = $request->validate([
            'receiver_id' => 'nullable|exists:users,id',
            'role_to_assign_id' => 'nullable|exists:roles,id',
        ]);

        if (auth()->user()->hasRole('super_admin')) {
            $request->validate([
                'school_id' => 'nullable|exists:schools,id',
            ]);
            if ($request->school_id) $validatedData["school_id"] = $request->school_id;
        }

        if (isset($validatedData["receiver_id"]) || isset($validatedData["school_id"])) $this->checkDuplicateInvitation(
            $validatedData["receiver_id"] ?? $invitation->receiver_id,
            $validatedData["school_id"] ?? $invitation->school_id
        );

        $invitation->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'School invitation updated successfully',
            'data' => $invitation
        ]);
    }

    public function destroy($id)
    {
        $invitation = SchoolInvitation::findOrFail($id);
        $invitation->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'School invitation deleted successfully'
        ]);
    }
}
