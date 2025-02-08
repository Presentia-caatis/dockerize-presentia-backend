<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ADMSMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);


        $validToken = config('app.adms_token');
        
        if (!$validToken || $token !== $validToken) {
            abort(401, "Unauthorized. Invalid token.");
        }

        return $next($request);
    }
}
