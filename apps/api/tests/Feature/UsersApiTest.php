<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_roles_and_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'admin');

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('data.0.username', 'admin');
    }

    public function test_admin_can_create_update_and_delete_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/v1/users', [
            'name' => 'Caja Demo',
            'username' => 'caja-demo',
            'display_name' => 'Caja Demo',
            'email' => 'caja@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.username', 'caja-demo');

        $userId = $createResponse->json('data.id');

        $this->patchJson("/api/v1/users/{$userId}", [
            'name' => 'Caja Principal',
            'username' => 'caja-principal',
            'display_name' => 'Caja Principal',
            'email' => 'caja-principal@example.com',
            'role_id' => $cashierRoleId,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);
    }

    public function test_cashier_cannot_manage_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'test-cashier-id',
            'name' => 'Usuario Ventas',
            'username' => 'ventas',
            'display_name' => 'Ventas',
            'email' => 'ventas@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/users')->assertForbidden();
    }
}
