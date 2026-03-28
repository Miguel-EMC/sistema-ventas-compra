<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_build_draft_and_checkout_sale(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $customer = Customer::query()->create([
            'public_id' => 'customer-sales',
            'name' => 'Cliente POS',
            'document_type' => 'cedula',
            'document_number' => '0102030405',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Cafe americano',
            salePrice: 3,
            quantity: 10,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 40,
            'notes' => 'Caja abierta para ventas',
        ])->assertCreated();

        $this->getJson('/api/v1/sales/draft')
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
            'notes' => 'Consumir en local',
        ])->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.total_items', 2)
            ->assertJsonPath('data.subtotal', 6);

        $checkoutResponse = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 10,
            'document_type' => 'ticket',
            'notes' => 'Pago completo',
        ])->assertCreated()
            ->assertJsonPath('data.grand_total', 6)
            ->assertJsonPath('data.change_total', 4)
            ->assertJsonPath('data.items_count', 1);

        $saleId = $checkoutResponse->json('data.id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'customer_id' => $customer->id,
            'cash_session_id' => $this->getOpenSessionIdFor($cashier),
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $saleId,
            'method' => 'cash',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'quantity' => -2,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'type' => 'income',
            'amount' => 6,
        ]);

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonPath('data.0.id', $saleId);

        $currentStock = Product::query()
            ->withSum('stockMovements as current_stock', 'quantity')
            ->findOrFail($product->id)
            ->current_stock;

        $this->assertSame(8.0, (float) $currentStock);
    }

    public function test_checkout_requires_open_cash_session(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Sandwich mixto',
            salePrice: 5,
            quantity: 1,
        );

        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 20,
            'document_type' => 'ticket',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cash_session.0', 'Debes abrir una caja antes de confirmar ventas.');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_checkout_rejects_insufficient_stock_when_cash_is_open(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Sandwich mixto',
            salePrice: 5,
            quantity: 1,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 20,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 20,
            'document_type' => 'ticket',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.draft.0', "No hay stock suficiente para '{$product->name}'.");

        $this->assertDatabaseCount('sales', 0);
    }

    private function createCashier(): User
    {
        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        return User::query()->create([
            'public_id' => 'cashier-sales',
            'name' => 'Usuario Ventas',
            'username' => 'ventas-pos',
            'display_name' => 'Ventas POS',
            'email' => 'ventas-pos@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);
    }

    private function createProductWithStock(string $name, float $salePrice, float $quantity): Product
    {
        /** @var ProductCategory $category */
        $category = ProductCategory::query()->firstOrFail();

        $product = Product::query()->create([
            'public_id' => "product-{$name}",
            'name' => $name,
            'sku' => Str($name)->slug()->upper()->value(),
            'category_id' => $category->id,
            'sale_price' => $salePrice,
            'cost_price' => max($salePrice / 2, 1),
            'tax_rate' => 0,
            'unit' => 'unidad',
            'track_stock' => true,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'type' => 'opening_balance',
            'reason' => 'test_seed',
            'quantity' => $quantity,
            'unit_cost' => $product->cost_price,
            'occurred_at' => now(),
        ]);

        return $product;
    }

    private function getOpenSessionIdFor(User $user): int
    {
        return (int) \App\Models\CashSession::query()
            ->where('opened_by_id', $user->id)
            ->where('status', 'open')
            ->value('id');
    }
}
