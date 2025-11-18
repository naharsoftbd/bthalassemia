<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
            // Token is valid
            JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {

            if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                throw new UnauthorizedHttpException('jwt-auth', 'Invalid token');
            }

            if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                try {
                    // Attempt refresh
                    $newToken = JWTAuth::refresh(JWTAuth::getToken());

                    // Set new token in response header
                    return $next($request)->header('Authorization', 'Bearer '.$newToken);
                } catch (Exception $e) {
                    throw new UnauthorizedHttpException('jwt-auth', 'Token expired & refresh failed');
                }
            }

            throw new UnauthorizedHttpException('jwt-auth', 'Unauthorized');
        }

        return $next($request);
    }
}
