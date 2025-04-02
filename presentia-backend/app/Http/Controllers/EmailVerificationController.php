<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['status' => 'success', 'message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['status' => 'success', 'message' => 'Verification link sent.']);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::where('id', $id)->firstOrFail();

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['status' => 'success', 'message' => 'Email already verified.'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['status' => 'success', 'message' => 'Email successfully verified.']);
    }
}
