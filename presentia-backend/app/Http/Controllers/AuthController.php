<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request){
        $validatedData = $request->validate([
            'fullname' => 'required|string|min:3|max:100|regex:/^[a-zA-Z \'\\\\]+$/',
            'username' => 'required|string|alpha_dash|min:3|max:50|unique:users,username',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'google_id' => 'required|string'
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);
        
        $user = User::create($validatedData);

        $token = $user->createToken($request->username);
        
        return response()->json([
            'status' => 'success',
            'user' => $user,
            'token' => $token->plainTextToken
        ], 200);
    }

    public function login(Request $request){
        $request->validate([
            'email_or_username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginIdentifier = $request->email_or_username;

        $user = filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL)
        ? User::where('email', $loginIdentifier)->first()
        : User::where('username', $loginIdentifier)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'status' => 'failed',
                'message' => 'The provided credentials are incorrect'
            ]);
        }

        $token =  $user->createToken($request->email_or_username);

        return response()->json([
            'status' => 'success',
            "user" => $user,
            "token" => $token->plainTextToken,
        ], 200);
    }
    public function logout(Request $request){
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
    
            return response()->json([
                'status' => 'success',
                'message' => 'You are logged out'
            ]);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated'
        ], 401);
    }   
}
