<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SocialiteController extends Controller
{
    /**
     * This function will be redirect to google
     */
    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * This function will authenticate the user through the google account
     * @return void
     */
    public function googleAuthentication() 
    {

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                return redirect(config('fe_url') . '/login?status=new_user&name=' . urlencode($googleUser->name) . '&email=' . urlencode($googleUser->email) . '&google_id=' . urlencode($googleUser->id));
            }

            dd($user);

            Auth::login($user, true);
            $token = $user->createToken('api-token')->plainTextToken;

            return redirect(config('fe_url') . '/login?status=existing_user&token=' . $token);

        } catch (\Exception $e) {
            return redirect(config('fe_url') . '/login?status=error&message=' . urlencode('Authentication failed.'));
        }
    }
}
