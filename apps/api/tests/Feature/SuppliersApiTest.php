<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuppliersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_and_delete_suppliers(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/suppliers', [
            'document_type' => 'ruc',
            'document_number' => '1790098765001',
            'name' => 'Proveedor Demo',
            'email' => 'proveedor@example.com',
            'phone' => '022222222',
            'address' => 'Zona industrial',
            'is_active' => true,
        ])->assertCreated();

        $supplierId = $response->json('data.id');

        $this->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Proveedor Demo');

        $this->patchJson("/api/v1/suppliers/{$supplierId}", [
            'document_type' => 'ruc',
            'document_number' => '1790098765001',
            'name' => 'Proveedor Demo Actualizado',
            'email' => 'proveedor+editado@example.com',
            'phone' => '033333333',
            'address' => 'Bodega central',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.name', 'Proveedor Demo Actualizado');

        $this->deleteJson("/api/v1/suppliers/{$supplierId}")
            ->assertOk();

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplierId,
        ]);
    }

    public function test_authenticated_cashier_can_list_suppliers_but_cannot_manage_them(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'cashier-suppliers',
            'name' => 'Usuario Proveedores',
            'username' => 'proveedores',
            'display_name' => 'Proveedores',
            'email' => 'proveedores@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Supplier::query()->create([
            'public_id' => 'supplier-demo',
            'name' => 'Proveedor visible',
            'document_type' => 'ruc',
            'document_number' => '1790000000001',
            'email' => 'supplier@example.com',
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/suppliers')
            ->assertOk();

        $this->postJson('/api/v1/suppliers', [
            'name' => 'Intento no autorizado',
        ])->assertForbidden();
    }
}
