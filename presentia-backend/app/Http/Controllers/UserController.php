<?php

namespace App\Http\Controllers;

use App\Filterable;
use App\Models\School;
use App\Models\User;
use App\Sortable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Illuminate\Validation\Rules;
use function App\Helpers\current_school_id;

class UserController extends Controller
{

    use Filterable, Sortable;

    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $query = User::query();
        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        $data = $query->with('roles:name')->paginate($perPage);

        $data->getCollection()->transform(function ($user) {
            if ($user->profile_image_path) {
                $user->profile_image_path = asset('storage/' . $user->profile_image_path);
            }
            return $user;
        });


        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $data->load('school')
        ]);
    }

    public function assignToSchool(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'school_id' => 'nullable|exists:schools,id'
        ]);

        // If the authenticated user is NOT a super_admin, use their current_school_id instead
        if (!auth()->user()->hasRole('super_admin')) {
            $user->school_id = current_school_id();
        } else {
            // If super_admin, allow setting any school_id from the request
            $user->school_id = $request->school_id;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User assigned to school successfully',
            'data' => $user->load('school')
        ], 201);
    }

    public function assignToSchoolViaToken(Request $request)
    {
        $request->validate([
            'school_token' => 'required|exists:schools,school_token'
        ], [
            'school_token.exists' => 'Invalid school token'
        ]);

        $user = $request->user();
        $user->school_id = School::where("school_token", $request->school_token)->first()?->id;
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'User assigned to school successfully',
            'data' => $user->load('school')
        ], 201);
    }

    public function removeFromSchool($id)
    {
        $user = User::findOrFail($id);
        if($user->school_id != auth()->user()->school_id){
            abort(403, 'You do not have the authority to remove a user from a school that does not assign to you.');
        }

        $user->school_id = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User removed from school successfully',
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'fullname' => 'required|string|min:3|max:100|regex:/^[a-zA-Z \'\\\\]+$/',
            'username' => 'required|string|alpha_dash|min:3|max:50|unique:users,username',
            'school_id' => 'nullable|exists:schools,id',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);

        if ($request->hasFile('profile_image')) {
            $validatedData['profile_image_path'] = $request->file('profile_image')->store($request->file('profile_image')->extension(), 'public');
        }
        ;

        $validatedData['password'] = Hash::make($request->password);

        $user = User::create($validatedData);

        $user->profile_image_path = asset('storage/' . $user->profile_image_path);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function getById($id)
    {
        $user = User::findOrFail($id);

        if ($user->profile_image_path) {
            $user->profile_image_path = asset('storage/' . $user->profile_image_path);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => $user->load('school')
        ]);
    }

    public function getByToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'User not authenticated');
        }

        if ($user->profile_image_path) {
            $user->profile_image_path = asset('storage/' . $user->profile_image_path);
        }
        ;

        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validatedData = $request->validate([
            'fullname' => 'nullable|string|min:3|max:100|regex:/^[a-zA-Z \'\\\\]+$/',
            'username' => 'nullable|string|alpha_dash|min:3|max:50|unique:users,username,' . $user->id,
            'old_password' => 'nullable|string',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'remove_image' => 'sometimes|boolean',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);

        if (!empty($validatedData['password'])) {
            if (empty($validatedData['old_password'])) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Old password is required to change the password'
                ], 400);
            }

            if (!Hash::check($validatedData['old_password'], $user->password)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Old password is incorrect'
                ], 400);
            }

            // Hash the new password before saving
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); // Ensure password isn't updated if not provided
        }

        if (isset($validatedData['remove_image']) && $validatedData['remove_image']) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $user->profile_image_path = null;
        } else if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $user->profile_image_path = $request->file('profile_image')->store($request->file('profile_image')->extension(), 'public');
        }

        $user->update($validatedData);

        if (empty($validatedData['remove_image']) || !$validatedData['remove_image']) {
            $user->profile_image_path = $user->profile_image_path ? asset('storage/' . $user->profile_image_path) : null;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->profile_image_path) {
            Storage::disk('public')->delete($user->profile_image_path);
        }
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}
