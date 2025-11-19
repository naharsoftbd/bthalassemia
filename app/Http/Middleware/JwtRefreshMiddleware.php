<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtRefreshMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::parseToken()->authenticate();

        } catch (TokenExpiredException $e) {

            try {
                // Refresh token
                $newToken = JWTAuth::refresh($token);

                // Send new token to client
                return $next($request)
                    ->header('X-Refreshed-Token', $newToken);

            } catch (JWTException $e) {
                return response()->json(['message' => 'Token expired, please login again'], 401);
            }
        }

        return $next($request);
    }
}
