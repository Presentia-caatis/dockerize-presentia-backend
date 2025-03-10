<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1'
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $data = User::paginate($perPage);

        $data->getCollection()->transform(function ($user) {
            if ($user->profile_image_path) {
                $user->profile_image_path =  asset('storage/' . $user->profile_image_path);
            }
            return $user;
        });


        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $data->load('school')
        ]);
    }

    public function linkToSchool(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'school_id' => 'nullable|exists:schools,id'
        ]);

        $user->school_id = $request->school_id;

        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Schools retrieved successfully',
            'data' => $user->load('school')
        ], 201);
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
        };

        $validatedData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);

        $user = User::create($validatedData);

        $user->profile_image_path =  asset('storage/' . $user->profile_image_path);

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
            $user->profile_image_path =  asset('storage/' . $user->profile_image_path);
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
        };

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
            'username' => 'nullable|string|alpha_dash|min:3|max:50|unique:users,username,'.$user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'remove_image' => 'sometimes|boolean',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png'
        ]);


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

        if (isset($validatedData['password'])) $validatedData['password'] = \Illuminate\Support\Facades\Hash::make($validatedData['password']);

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
