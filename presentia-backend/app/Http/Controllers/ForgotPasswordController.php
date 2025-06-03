<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Str;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(
                [
                    'status' => 'success',
                    'message' => __($status)
                ]
            );
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully. Please login.',
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
