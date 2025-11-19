<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\AuthRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Services\ApiResponseService;
use App\Services\Auth\AuthService;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        event(new Registered($user));

        return ApiResponseService::success($user, 'User created successfully');
    }

    public function login(AuthRequest $request)
    {
        if (! $token = $this->authService->login($request->validated())) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $userData = $this->authService->respondWithToken($token);

        return ApiResponseService::success($userData);
    }

    public function me()
    {
        $userData = $this->authService->me();

        return ApiResponseService::success($userData);
    }

    public function refresh()
    {
        $userData = $this->authService->refresh();

        return ApiResponseService::success($userData);
    }

    public function logout()
    {
        $this->authService->logout();

        return ApiResponseService::success([], 'Successfully logged out!');
    }
}
