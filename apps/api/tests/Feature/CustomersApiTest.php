<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_and_delete_customers(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/customers', [
            'document_type' => 'cedula',
            'document_number' => '0102030405',
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.com',
            'phone' => '0999999999',
            'address' => 'Av. Principal',
            'is_active' => true,
        ])->assertCreated();

        $customerId = $response->json('data.id');

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cliente Demo');

        $this->patchJson("/api/v1/customers/{$customerId}", [
            'document_type' => 'ruc',
            'document_number' => '1790012345001',
            'name' => 'Cliente Demo Actualizado',
            'email' => 'cliente+editado@example.com',
            'phone' => '0888888888',
            'address' => 'Av. Secundaria',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.name', 'Cliente Demo Actualizado');

        $this->deleteJson("/api/v1/customers/{$customerId}")
            ->assertOk();

        $this->assertSoftDeleted('customers', [
            'id' => $customerId,
        ]);
    }

    public function test_authenticated_cashier_can_list_customers_but_cannot_manage_them(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'cashier-customers',
            'name' => 'Usuario Clientes',
            'username' => 'clientes',
            'display_name' => 'Clientes',
            'email' => 'clientes@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Customer::query()->create([
            'public_id' => 'customer-demo',
            'name' => 'Cliente visible',
            'document_type' => 'cedula',
            'document_number' => '0101010101',
            'email' => 'visible@example.com',
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/customers')
            ->assertOk();

        $this->postJson('/api/v1/customers', [
            'name' => 'Intento no autorizado',
        ])->assertForbidden();
    }
}
