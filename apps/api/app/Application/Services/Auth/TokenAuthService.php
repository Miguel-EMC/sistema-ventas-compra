<?php

namespace App\Application\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenAuthService
{
    /**
     * @param array{login:string,password:string,device_name?:string|null} $credentials
     * @return array{user:User,token:string}
     */
    public function attempt(array $credentials): array
    {
        $user = User::query()
            ->with('role')
            ->where(function ($query) use ($credentials): void {
                $query->where('email', $credentials['login'])
                    ->orWhere('username', $credentials['login']);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Las credenciales proporcionadas no son validas.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'El usuario esta inactivo y no puede ingresar.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user
            ->createToken($credentials['device_name'] ?? 'ventaspos-web')
            ->plainTextToken;

        return [
            'user' => $user->fresh('role'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
