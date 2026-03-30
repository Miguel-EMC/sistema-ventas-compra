<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\CashRegister;
use App\Models\CashSession;
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

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/cancel", [
            'cancellation_reason' => 'Proveedor retiro la mercaderia por error de despacho.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_by.username', 'admin')
            ->assertJsonPath('data.cancellation_reason', 'Proveedor retiro la mercaderia por error de despacho.');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrderId,
            'status' => 'cancelled',
            'cancelled_by_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $coffee->id,
            'type' => 'purchase_return',
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
            'quantity' => -5,
        ]);

        $this->assertEquals(
            0.0,
            (float) StockMovement::query()->where('reference_id', $purchaseOrderId)->sum('quantity')
        );

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

        $this->postJson("/api/v1/purchase-orders/{$secondOrderId}/cancel", [
            'cancellation_reason' => 'Compra duplicada.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $secondOrderId,
            'status' => 'cancelled',
        ]);

        $thirdOrderResponse = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-003',
            'items' => [
                [
                    'product_id' => $sugar->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => 1.30,
                ],
            ],
        ])->assertCreated();

        $thirdOrderId = $thirdOrderResponse->json('data.id');

        $this->deleteJson("/api/v1/purchase-orders/{$thirdOrderId}")
            ->assertOk();

        $this->assertDatabaseMissing('purchase_orders', [
            'id' => $thirdOrderId,
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

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrder->id}/cancel", [
            'cancellation_reason' => 'No autorizado',
        ])->assertForbidden();
    }

    public function test_admin_can_register_partial_purchase_return_and_cancel_remaining_stock(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Proveedor Devolucion',
            'document_type' => 'ruc',
            'document_number' => '1790033333001',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Leche entera',
            'sku' => 'LEC-01',
            'category_id' => $category->id,
            'sale_price' => 1.90,
            'cost_price' => 1.00,
            'tax_rate' => 0,
            'unit' => 'lt',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $purchaseOrderId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-RET-001',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 1.20,
                ],
            ],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
            'received_at' => '2026-03-28 12:00:00',
        ])->assertOk();

        $orderDetail = $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->assertJsonPath('data.items.0.remaining_returnable_quantity', 5);

        $purchaseOrderItemId = $orderDetail->json('data.items.0.id');

        $purchaseReturnId = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'returned_at' => '2026-03-28 13:00:00',
            'reason' => 'Proveedor solicita devolucion parcial por lote defectuoso.',
            'notes' => 'Se devuelve una parte del despacho.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 2,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.return_total', 2.4)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->json('data.id');

        $this->assertDatabaseHas('purchase_returns', [
            'id' => $purchaseReturnId,
            'purchase_order_id' => $purchaseOrderId,
            'return_total' => 2.4,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'purchase_return',
            'reference_id' => $purchaseReturnId,
            'type' => 'purchase_return',
            'quantity' => -2,
        ]);

        $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->assertJsonPath('data.returns_count', 1)
            ->assertJsonPath('data.returned_total', 2.4)
            ->assertJsonPath('data.items.0.returned_quantity', 2)
            ->assertJsonPath('data.items.0.remaining_returnable_quantity', 3)
            ->assertJsonPath('data.returns.0.return_total', 2.4);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/cancel", [
            'cancellation_reason' => 'Se cierra la orden tras devolucion parcial al proveedor.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
            'type' => 'purchase_return',
            'quantity' => -3,
        ]);

        $currentStock = Product::query()
            ->withSum('stockMovements as current_stock', 'quantity')
            ->findOrFail($product->id)
            ->current_stock;

        $this->assertSame(0.0, (float) $currentStock);
    }

    public function test_purchase_return_requires_admin_received_order_and_valid_remaining_stock(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'purchase-return-cashier-id',
            'name' => 'Cajero Devolucion Compra',
            'username' => 'cajero-devol-compra',
            'display_name' => 'Cajero Devolucion Compra',
            'email' => 'cajero-devol-compra@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Proveedor Restricciones',
            'document_type' => 'ruc',
            'document_number' => '1790044444001',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Mantequilla',
            'sku' => 'MAN-01',
            'category_id' => $category->id,
            'sale_price' => 2.80,
            'cost_price' => 1.50,
            'tax_rate' => 0,
            'unit' => 'kg',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $purchaseOrderId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-RET-002',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 2,
                    'unit_cost' => 1.70,
                ],
            ],
        ])->assertCreated()->json('data.id');

        $purchaseOrderItemId = $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->json('data.items.0.id');

        Sanctum::actingAs($cashier);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'reason' => 'No autorizado',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertForbidden();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'reason' => 'Aun no recibida.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.purchase_order.0', 'Solo puedes devolver compras que ya fueron recibidas.');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
            'received_at' => '2026-03-28 14:00:00',
        ])->assertOk();

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'sale',
            'reason' => 'test_consumption',
            'reference_type' => 'test',
            'reference_id' => 1,
            'quantity' => -2,
            'unit_cost' => $product->cost_price,
            'occurred_at' => now(),
        ]);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'reason' => 'Sin stock para devolver.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonFragment([
                "No hay stock suficiente para devolver '{$product->name}'.",
            ]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'adjustment_in',
            'reason' => 'test_restore',
            'reference_type' => 'test',
            'reference_id' => 2,
            'quantity' => 2,
            'unit_cost' => $product->cost_price,
            'occurred_at' => now(),
        ]);

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'reason' => 'Primera devolucion valida.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'reason' => 'Exceso de devolucion.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 2,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonFragment([
                "Solo puedes devolver hasta 1 unidad(es) de '{$product->name}'.",
            ]);
    }

    public function test_admin_can_register_purchase_payments_and_track_balance(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Proveedor Pagos',
            'document_type' => 'ruc',
            'document_number' => '1790055555001',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Queso maduro',
            'sku' => 'QUE-01',
            'category_id' => $category->id,
            'sale_price' => 6.90,
            'cost_price' => 3.00,
            'tax_rate' => 0,
            'unit' => 'kg',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $purchaseOrderId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-PAY-001',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 5,
                    'unit_cost' => 3.00,
                ],
            ],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
            'received_at' => '2026-03-28 15:00:00',
        ])->assertOk();

        $purchaseOrderItemId = $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->json('data.items.0.id');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/returns", [
            'returned_at' => '2026-03-28 15:30:00',
            'reason' => 'Ajuste por mercaderia en mal estado.',
            'items' => [
                [
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'transfer',
            'amount' => 5,
            'reference' => 'TRX-001',
            'notes' => 'Abono inicial por transferencia.',
            'paid_at' => '2026-03-28 16:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.method', 'transfer')
            ->assertJsonPath('data.amount', 5)
            ->assertJsonPath('data.reference', 'TRX-001')
            ->assertJsonPath('data.cash_session', null);

        $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->assertJsonPath('data.returned_total', 3)
            ->assertJsonPath('data.net_payable_total', 12)
            ->assertJsonPath('data.paid_total', 5)
            ->assertJsonPath('data.balance_due', 7)
            ->assertJsonPath('data.payment_status', 'partial')
            ->assertJsonPath('data.payments_count', 1)
            ->assertJsonPath('data.payments.0.method', 'transfer');

        /** @var CashRegister $cashRegister */
        $cashRegister = CashRegister::query()->firstOrFail();

        CashSession::query()->create([
            'public_id' => (string) Str::uuid(),
            'cash_register_id' => $cashRegister->id,
            'opened_by_id' => $admin->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'open',
            'notes' => 'Caja abierta para pago a proveedor.',
        ]);

        $cashPaymentId = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'cash',
            'amount' => 7,
            'reference' => 'EF-001',
            'paid_at' => '2026-03-28 16:15:00',
        ])->assertCreated()
            ->assertJsonPath('data.method', 'cash')
            ->assertJsonPath('data.amount', 7)
            ->assertJsonPath('data.cash_session.register_name', 'Caja principal')
            ->json('data.id');

        $this->assertDatabaseHas('cash_movements', [
            'reference_type' => 'purchase_order_payment',
            'reference_id' => $cashPaymentId,
            'type' => 'expense',
            'category' => 'purchase_payment',
            'amount' => 7,
        ]);

        $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}")
            ->assertOk()
            ->assertJsonPath('data.net_payable_total', 12)
            ->assertJsonPath('data.paid_total', 12)
            ->assertJsonPath('data.balance_due', 0)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payments_count', 2)
            ->assertJsonPath('data.payments.0.method', 'cash');
    }

    public function test_purchase_payment_requires_received_order_open_cash_session_and_available_balance(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Proveedor Restricciones Pago',
            'document_type' => 'ruc',
            'document_number' => '1790066666001',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Harina premium',
            'sku' => 'HAR-01',
            'category_id' => $category->id,
            'sale_price' => 2.50,
            'cost_price' => 1.40,
            'tax_rate' => 0,
            'unit' => 'kg',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $purchaseOrderId = $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'reference' => 'OC-PAY-002',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 4,
                    'unit_cost' => 2.00,
                ],
            ],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'transfer',
            'amount' => 2,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.purchase_order.0', 'Solo puedes registrar pagos sobre compras recibidas.');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
            'received_at' => '2026-03-28 17:00:00',
        ])->assertOk();

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'cash',
            'amount' => 2,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cash_session.0', 'Debes abrir una caja para registrar pagos en efectivo.');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'transfer',
            'amount' => 10,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'Solo puedes registrar hasta 8 en esta compra.');

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/payments", [
            'method' => 'transfer',
            'amount' => 3,
        ])->assertCreated();

        $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/cancel", [
            'cancellation_reason' => 'Intento no permitido con pagos ya aplicados.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.purchase_order.0', 'No puedes anular una compra con pagos registrados.');
    }
}
