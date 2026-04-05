<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            ->assertJsonFragment(['slug' => 'admin'])
            ->assertJsonFragment(['slug' => 'superadmin']);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['username' => 'admin'])
            ->assertJsonMissing(['username' => 'superadmin']);
    }

    public function test_superadmin_can_list_roles_and_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::query()->where('username', 'superadmin')->firstOrFail();
        Sanctum::actingAs($superadmin);

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'admin'])
            ->assertJsonFragment(['slug' => 'superadmin']);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['username' => 'admin'])
            ->assertJsonFragment(['username' => 'superadmin']);
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

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'company_id' => $admin->company_id,
        ]);

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

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/users/{$admin->id}", [
            'name' => $admin->name,
            'username' => $admin->username,
            'display_name' => $admin->display_name,
            'email' => $admin->email,
            'role_id' => $admin->role_id,
            'is_active' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $superadminRoleId = Role::query()->where('slug', 'superadmin')->value('id');

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Escalada',
            'username' => 'escalada',
            'display_name' => 'Escalada',
            'email' => 'escalada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $superadminRoleId,
            'is_active' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    public function test_superadmin_must_assign_company_to_tenant_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::query()->where('username', 'superadmin')->firstOrFail();
        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        Sanctum::actingAs($superadmin);

        $this->postJson('/api/v1/users', [
            'name' => 'Tenant suelto',
            'username' => 'tenant-suelto',
            'display_name' => 'Tenant Suelto',
            'email' => 'tenant-suelto@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_superadmin_can_create_company_scoped_user(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::query()->where('username', 'superadmin')->firstOrFail();
        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');
        $demoCompanyId = Company::query()->where('domain', 'demo')->value('id');

        Sanctum::actingAs($superadmin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Caja Tenant',
            'username' => 'caja-tenant',
            'display_name' => 'Caja Tenant',
            'email' => 'caja-tenant@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $cashierRoleId,
            'company_id' => $demoCompanyId,
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.username', 'caja-tenant');

        $this->assertDatabaseHas('users', [
            'username' => 'caja-tenant',
            'company_id' => $demoCompanyId,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes eliminar tu propio usuario.');
    }

    public function test_cashier_cannot_manage_users(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => (string) Str::uuid(),
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
