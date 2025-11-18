<?php

namespace App\Services\Auth;

use App\Interfaces\Auth\AuthRepositoryInterface;

class AuthService
{
    protected $authRepo;

    public function __construct(
        AuthRepositoryInterface $authRepo
    ) {
        $this->authRepo = $authRepo;
    }

    public function register($data)
    {
        return $this->authRepo->register($data);
    }

    public function login($data)
    {
        return $this->authRepo->login($data);
    }

    public function me()
    {
        return $this->authRepo->me();
    }

    public function refresh()
    {
        return $this->authRepo->refresh();
    }

    public function logout()
    {
        return $this->authRepo->logout();
    }

    public function respondWithToken($token)
    {
        return $this->authRepo->respondWithToken($token);
    }
}
