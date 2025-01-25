<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckSingleSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is logged in and has multiple sessions
        if ($user && session()->getId() !== $user->current_session_id) {
            // Logout the current session
            Auth::logout();
            session()->invalidate();

            return response()->json([
                'status' => 'error',
                'message' => 'You are logged out because of a new login.',
            ], 403);
        }

        return $next($request);
    }
}
