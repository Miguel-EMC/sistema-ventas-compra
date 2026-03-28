<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchasesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_receive_and_delete_purchase_orders(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Proveedor Central',
            'document_type' => 'ruc',
            'document_number' => '1790011111001',
            'is_active' => true,
        ]);

        $coffee = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Cafe molido',
            'sku' => 'CAF-01',
            'category_id' => $category->id,
            'sale_price' => 4.50,
            'cost_price' => 2.00,
            'tax_rate' => 0,
            'unit' => 'kg',
            'track_stock' => true,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $sugar = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Azucar refinada',
            'sku' => 'AZU-01',
            'category_id' => $category->id,
            'sale_price' => 2.50,
            'cost_price' => 1.10,
            'tax_rate' => 0,
            'unit' => 'kg',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $createResponse = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-001',
            'ordered_at' => '2026-03-28 09:00:00',
            'notes' => 'Reposicion semanal',
            'items' => [
                [
                    'product_id' => $coffee->id,
                    'quantity_ordered' => 3,
                    'unit_cost' => 2.20,
                ],
                [
                    'product_id' => $sugar->id,
                    'quantity_ordered' => 4,
                    'unit_cost' => 1.25,
                ],
            ],
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'ordered')
            ->assertJsonPath('data.supplier.name', 'Proveedor Central')
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonPath('data.grand_total', 11.6);

        $purchaseOrderId = $createResponse->json('data.id');

        $this->patchJson("/api/v1/purchase-orders/{$purchaseOrderId}", [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-001-A',
            'ordered_at' => '2026-03-28 10:00:00',
            'notes' => 'Reposicion ajustada',
            'items' => [
                [
                    'product_id' => $coffee->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 2.30,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.reference', 'OC-001-A')
            ->assertJsonPath('data.grand_total', 11.5)
            ->assertJsonPath('data.items_count', 1);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
            'received_at' => '2026-03-28 11:00:00',
            'notes' => 'Mercaderia completa',
        ])->assertOk()
            ->assertJsonPath('data.status', 'received')
            ->assertJsonPath('data.received_by.username', 'admin')
            ->assertJsonPath('data.items.0.quantity_received', 5);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $coffee->id,
            'type' => 'purchase_in',
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
        ]);

        $this->assertEquals(5.0, (float) StockMovement::query()->where('reference_id', $purchaseOrderId)->sum('quantity'));
        $this->assertEquals(2.30, (float) $coffee->fresh()->cost_price);

        $secondOrderResponse = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-002',
            'items' => [
                [
                    'product_id' => $sugar->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => 1.30,
                ],
            ],
        ])->assertCreated();

        $secondOrderId = $secondOrderResponse->json('data.id');

        $this->deleteJson("/api/v1/purchase-orders/{$secondOrderId}")
            ->assertOk();

        $this->assertDatabaseMissing('purchase_orders', [
            'id' => $secondOrderId,
        ]);
    }

    public function test_cashier_can_view_purchase_orders_but_cannot_manage_them(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'purchase-cashier-id',
            'name' => 'Usuario Compras',
            'username' => 'compras-caja',
            'display_name' => 'Compras Caja',
            'email' => 'compras-caja@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'public_id' => (string) Str::uuid(),
            'status' => 'ordered',
            'ordered_at' => now(),
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/purchase-orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $purchaseOrder->id);

        $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => 1,
            'items' => [],
        ])->assertForbidden();
    }
}
