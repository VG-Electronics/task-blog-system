<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(array $data): string
    {
        $user = User::create($data);

        $user->roles()->attach(Role::where('name', Role::USER)->first());

        return $user->createToken('api')->plainTextToken;
    }

    public function login(array $data): ?string
    {
        if (!Auth::attempt($data)) {
            return null;
        }

        return Auth::user()->createToken('api')->plainTextToken;
    }

    public function logout(): void
    {
        Auth::user()->currentAccessToken()->delete();
    }
}
