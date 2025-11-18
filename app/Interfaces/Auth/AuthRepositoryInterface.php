<?php

namespace App\Interfaces\Auth;

interface AuthRepositoryInterface
{
    public function register(array $data);

    public function login(array $credentials);

    public function me();

    public function refresh();

    public function logout();

    public function respondWithToken($token);
}
