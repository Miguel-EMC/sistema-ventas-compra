<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_superadmin_can_log_in_with_username_and_fetch_me(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'superadmin',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.user.username', 'superadmin')
            ->assertJsonPath('data.user.role.slug', 'superadmin');

        $token = $loginResponse->json('meta.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'superadmin')
            ->assertJsonPath('data.role.slug', 'superadmin');
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
}
