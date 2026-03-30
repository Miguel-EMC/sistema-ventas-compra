<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_with_username_and_fetch_me(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.user.username', 'admin');

        $token = $loginResponse->json('meta.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'admin');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'admin',
            'password' => 'incorrecta',
        ])->assertUnprocessable();
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $token = $admin->createToken('phpunit')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertCount(0, $admin->fresh()->tokens);
    }

    public function test_legacy_bridge_can_issue_a_token_for_a_migrated_user(): void
    {
        $this->seed(CoreReferenceSeeder::class);
        Config::set('app.legacy_bridge_secret', 'phpunit-bridge-secret');

        $payload = [
            'login' => 'admin',
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addMinute()->timestamp,
        ];

        $encodedPayload = rtrim(strtr(base64_encode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encodedPayload, 'phpunit-bridge-secret');

        $this->postJson('/api/v1/auth/legacy-bridge', [
            'token' => $encodedPayload.'.'.$signature,
            'device_name' => 'phpunit-legacy-bridge',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.username', 'admin')
            ->assertJsonPath('meta.token_type', 'Bearer');
    }

    public function test_legacy_bridge_rejects_invalid_signature(): void
    {
        $this->seed(CoreReferenceSeeder::class);
        Config::set('app.legacy_bridge_secret', 'phpunit-bridge-secret');

        $payload = [
            'login' => 'admin',
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addMinute()->timestamp,
        ];

        $encodedPayload = rtrim(strtr(base64_encode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');

        $this->postJson('/api/v1/auth/legacy-bridge', [
            'token' => $encodedPayload.'.firma-invalida',
        ])->assertUnprocessable();
    }
}
