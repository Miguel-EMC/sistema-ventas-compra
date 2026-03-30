<?php

namespace App\Application\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class LegacyBridgeAuthService
{
    /**
     * @param array{token:string,device_name?:string|null} $payload
     * @return array{user:User,token:string}
     */
    public function exchange(array $payload): array
    {
        $claims = $this->parseToken($payload['token']);

        $user = User::query()
            ->with('role')
            ->where(function ($query) use ($claims): void {
                $query->where('username', $claims['login'])
                    ->orWhere('email', $claims['login']);
            })
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => 'No existe un usuario migrado para la sesion legacy solicitada.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'token' => 'El usuario migrado esta inactivo y no puede ingresar.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $user
            ->createToken($payload['device_name'] ?? 'legacy-bridge')
            ->plainTextToken;

        return [
            'user' => $user->fresh('role'),
            'token' => $token,
        ];
    }

    /**
     * @return array{login:string,issued_at:int,expires_at:int}
     */
    private function parseToken(string $signedToken): array
    {
        $secret = trim((string) Config::get('app.legacy_bridge_secret', ''));

        if ($secret === '') {
            throw ValidationException::withMessages([
                'token' => 'El bridge legacy no esta configurado en la API.',
            ]);
        }

        $parts = explode('.', $signedToken, 2);
        if (count($parts) !== 2) {
            throw ValidationException::withMessages([
                'token' => 'El token bridge no tiene un formato valido.',
            ]);
        }

        [$encodedPayload, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $secret);
        if (! hash_equals($expectedSignature, $signature)) {
            throw ValidationException::withMessages([
                'token' => 'La firma del bridge legacy no es valida.',
            ]);
        }

        $normalizedPayload = strtr($encodedPayload, '-_', '+/');
        $paddingLength = strlen($normalizedPayload) % 4;
        if ($paddingLength > 0) {
            $normalizedPayload .= str_repeat('=', 4 - $paddingLength);
        }

        $json = base64_decode($normalizedPayload, true);
        $payload = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'token' => 'El payload bridge no pudo decodificarse.',
            ]);
        }

        $login = trim((string) ($payload['login'] ?? ''));
        $issuedAt = (int) ($payload['issued_at'] ?? 0);
        $expiresAt = (int) ($payload['expires_at'] ?? 0);

        if ($login === '' || $issuedAt <= 0 || $expiresAt <= 0) {
            throw ValidationException::withMessages([
                'token' => 'El payload bridge esta incompleto.',
            ]);
        }

        if ($expiresAt < now()->timestamp) {
            throw ValidationException::withMessages([
                'token' => 'La sesion bridge ya expiro. Abre el modulo nuevamente desde el legacy.',
            ]);
        }

        return [
            'login' => $login,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];
    }
}
