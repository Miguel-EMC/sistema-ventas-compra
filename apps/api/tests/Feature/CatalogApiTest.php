<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_adjust_and_delete_products(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $categoryResponse = $this->postJson('/api/v1/product-categories', [
            'name' => 'Bebidas',
            'description' => 'Productos liquidos',
            'is_active' => true,
        ])->assertCreated();

        $categoryId = $categoryResponse->json('data.id');

        $productResponse = $this->postJson('/api/v1/products', [
            'name' => 'Jugo natural',
            'description' => 'Botella de 500 ml',
            'sku' => 'JG-001',
            'barcode' => '7790001',
            'category_id' => $categoryId,
            'sale_price' => 2.50,
            'cost_price' => 1.20,
            'tax_rate' => 0,
            'unit' => 'botella',
            'track_stock' => true,
            'minimum_stock' => 5,
            'initial_stock' => 12,
            'is_active' => true,
        ])->assertCreated();

        $productId = $productResponse->json('data.id');

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.current_stock', 12);

        $this->patchJson("/api/v1/products/{$productId}", [
            'name' => 'Jugo natural premium',
            'description' => 'Botella de 500 ml',
            'sku' => 'JG-001',
            'barcode' => '7790001',
            'category_id' => $categoryId,
            'sale_price' => 2.75,
            'cost_price' => 1.30,
            'tax_rate' => 0,
            'unit' => 'botella',
            'track_stock' => true,
            'minimum_stock' => 4,
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Jugo natural premium');

        $this->postJson("/api/v1/products/{$productId}/stock-adjustments", [
            'quantity' => -2,
            'reason' => 'conteo',
            'notes' => 'Ajuste de inventario',
        ])->assertOk()
            ->assertJsonPath('data.current_stock', 10);

        $this->deleteJson("/api/v1/products/{$productId}")
            ->assertOk();

        $this->assertSoftDeleted('products', [
            'id' => $productId,
        ]);
    }

    public function test_authenticated_cashier_can_list_products_but_cannot_manage_them(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');
        $category = ProductCategory::query()->firstOrFail();

        $cashier = User::query()->create([
            'public_id' => 'cashier-catalog',
            'name' => 'Usuario Catalogo',
            'username' => 'catalogo',
            'display_name' => 'Catalogo',
            'email' => 'catalogo@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Product::query()->create([
            'public_id' => 'product-catalog',
            'name' => 'Producto Demo',
            'sku' => 'PR-001',
            'category_id' => $category->id,
            'sale_price' => 5,
            'cost_price' => 2,
            'tax_rate' => 0,
            'unit' => 'unidad',
            'track_stock' => false,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/products')
            ->assertOk();

        $this->postJson('/api/v1/products', [
            'name' => 'Intento no autorizado',
            'sale_price' => 1,
            'cost_price' => 1,
            'tax_rate' => 0,
            'unit' => 'unidad',
            'track_stock' => false,
            'minimum_stock' => 0,
        ])->assertForbidden();
    }
}
