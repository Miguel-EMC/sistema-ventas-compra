<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\TaxResolution;
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
            ->assertJsonPath('data.paid_total', 6)
            ->assertJsonPath('data.balance_due', 0)
            ->assertJsonPath('data.payment_status', 'paid')
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
            'amount' => 6,
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

    public function test_cashier_can_checkout_sale_on_credit_and_register_follow_up_payments(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Wrap mediterraneo',
            salePrice: 5,
            quantity: 8,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 25,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'credit',
            'amount_paid' => 0,
            'document_type' => 'ticket',
            'notes' => 'Venta a credito inicial.',
        ])->assertCreated()
            ->assertJsonPath('data.paid_total', 0)
            ->assertJsonPath('data.balance_due', 10)
            ->assertJsonPath('data.payment_status', 'pending')
            ->json('data.id');

        $this->assertDatabaseCount('sale_payments', 0);

        $this->postJson("/api/v1/sales/{$saleId}/payments", [
            'method' => 'transfer',
            'amount' => 4,
            'reference' => 'TRX-1001',
            'notes' => 'Primer abono por transferencia.',
        ])->assertCreated()
            ->assertJsonPath('data.method', 'transfer')
            ->assertJsonPath('data.amount', 4);

        $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.paid_total', 4)
            ->assertJsonPath('data.balance_due', 6)
            ->assertJsonPath('data.payment_status', 'partial')
            ->assertJsonPath('data.payments_count', 1)
            ->assertJsonPath('data.payments.0.method', 'transfer');

        $cashPaymentId = $this->postJson("/api/v1/sales/{$saleId}/payments", [
            'method' => 'cash',
            'amount' => 6,
            'reference' => 'CAJA-ABONO-02',
            'notes' => 'Cierre de saldo en caja.',
        ])->assertCreated()
            ->assertJsonPath('data.method', 'cash')
            ->assertJsonPath('data.amount', 6)
            ->json('data.id');

        $this->assertDatabaseHas('cash_movements', [
            'reference_type' => 'sale_payment',
            'reference_id' => $cashPaymentId,
            'type' => 'income',
            'amount' => 6,
        ]);

        $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.paid_total', 10)
            ->assertJsonPath('data.balance_due', 0)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payments_count', 2)
            ->assertJsonPath('data.payments.0.method', 'cash')
            ->assertJsonPath('data.payments.1.method', 'transfer');
    }

    public function test_sale_payment_rejects_overpayment_and_cash_without_open_session(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Combo brunch',
            salePrice: 9,
            quantity: 6,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 30,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'credit',
            'amount_paid' => 0,
            'document_type' => 'ticket',
        ])->assertCreated()->json('data.id');

        $openSessionId = $this->getOpenSessionIdFor($cashier);

        $this->postJson('/api/v1/cash/sessions/' . $openSessionId . '/close', [
            'closing_amount' => 30,
            'notes' => 'Cierre para validar cobros.',
        ])->assertOk();

        $this->postJson("/api/v1/sales/{$saleId}/payments", [
            'method' => 'cash',
            'amount' => 3,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cash_session.0', 'Debes abrir una caja para registrar cobros en efectivo.');

        $this->postJson("/api/v1/sales/{$saleId}/payments", [
            'method' => 'transfer',
            'amount' => 12,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.amount.0', 'Solo puedes registrar hasta 9 en esta venta.');

        $this->postJson("/api/v1/sales/{$saleId}/payments", [
            'method' => 'transfer',
            'amount' => 5,
            'reference' => 'TRX-2002',
        ])->assertCreated()
            ->assertJsonPath('data.method', 'transfer')
            ->assertJsonPath('data.amount', 5);

        $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.paid_total', 5)
            ->assertJsonPath('data.balance_due', 4)
            ->assertJsonPath('data.payment_status', 'partial');
    }

    public function test_cashier_can_checkout_sale_with_factura_and_generate_invoice(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $customer = Customer::query()->create([
            'public_id' => 'customer-invoice',
            'name' => 'Cliente Factura',
            'document_type' => 'ruc',
            'document_number' => '1790012345001',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Capuccino doble',
            salePrice: 4.5,
            quantity: 8,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 30,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 9,
            'document_type' => 'factura',
            'notes' => 'Emitir con factura',
        ])->assertCreated()
            ->assertJsonPath('data.document_type', 'factura')
            ->assertJsonPath('data.invoice.invoice_number', '001-001-00000001')
            ->assertJsonPath('data.invoice.authorization_number', 'AUTH-DEMO-001')
            ->json('data.id');

        $this->assertDatabaseHas('invoices', [
            'sale_id' => $saleId,
            'customer_id' => $customer->id,
            'invoice_number' => '001-001-00000001',
            'authorization_number_snapshot' => 'AUTH-DEMO-001',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Capuccino doble',
            'line_total' => 9,
        ]);

        $this->assertSame(
            2,
            TaxResolution::query()->where('authorization_number', 'AUTH-DEMO-001')->value('next_invoice_number')
        );
    }

    public function test_cashier_can_register_partial_return_and_view_sale_detail(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $customer = Customer::query()->create([
            'public_id' => 'customer-return',
            'name' => 'Cliente Devolucion',
            'document_type' => 'cedula',
            'document_number' => '0912345678',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Brownie artesanal',
            salePrice: 3,
            quantity: 10,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 20,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 6,
            'document_type' => 'ticket',
        ])->assertCreated()->json('data.id');

        $detailResponse = $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.items.0.remaining_quantity', 2)
            ->assertJsonPath('data.returns_count', 0);

        $saleItemId = $detailResponse->json('data.items.0.id');

        $returnId = $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'cash',
            'reason' => 'Producto entregado en mal estado.',
            'notes' => 'Cliente devuelve una unidad.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                    'reason' => 'Unidad defectuosa',
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.refund_total', 3)
            ->assertJsonPath('data.refund_method', 'cash')
            ->assertJsonPath('data.items.0.quantity', 1)
            ->json('data.id');

        $this->assertDatabaseHas('sale_returns', [
            'id' => $returnId,
            'sale_id' => $saleId,
            'refund_method' => 'cash',
            'refund_total' => 3,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'sale_return',
            'reference_id' => $returnId,
            'type' => 'sale_return',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'reference_type' => 'sale_return',
            'reference_id' => $returnId,
            'type' => 'expense',
            'amount' => 3,
        ]);

        $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.returns_count', 1)
            ->assertJsonPath('data.returned_total', 3)
            ->assertJsonPath('data.items.0.returned_quantity', 1)
            ->assertJsonPath('data.items.0.remaining_quantity', 1);

        $currentStock = Product::query()
            ->withSum('stockMovements as current_stock', 'quantity')
            ->findOrFail($product->id)
            ->current_stock;

        $this->assertSame(9.0, (float) $currentStock);
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

    public function test_partial_return_rejects_invalid_remaining_quantity_and_cash_session_rules(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Cheesecake',
            salePrice: 5,
            quantity: 4,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 15,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 10,
            'document_type' => 'ticket',
        ])->assertCreated()->json('data.id');

        $saleItemId = $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->json('data.items.0.id');

        $openSessionId = $this->getOpenSessionIdFor($cashier);

        $this->postJson('/api/v1/cash/sessions/' . $openSessionId . '/close', [
            'closing_amount' => 25,
            'notes' => 'Cierre para prueba de devolucion.',
        ])->assertOk();

        $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'cash',
            'reason' => 'Intento sin caja.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cash_session.0', 'Debes abrir una caja para devolver efectivo.');

        $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'card',
            'reason' => 'Primera devolucion parcial.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'card',
            'reason' => 'Exceso de devolucion.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 2,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonFragment([
                "Solo puedes devolver hasta 1 unidad(es) de '{$product->name}'.",
            ]);
    }

    public function test_admin_can_issue_credit_note_for_invoiced_sale_return(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $customer = Customer::query()->create([
            'public_id' => 'customer-credit-note',
            'name' => 'Cliente Nota Credito',
            'document_type' => 'ruc',
            'document_number' => '1790011111001',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Cheesecake premium',
            salePrice: 6.5,
            quantity: 6,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 25,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 13,
            'document_type' => 'factura',
        ])->assertCreated()
            ->assertJsonPath('data.invoice.invoice_number', '001-001-00000001')
            ->json('data.id');

        $saleItemId = $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->json('data.items.0.id');

        $saleReturnId = $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'card',
            'reason' => 'Cliente reporta diferencia en el producto.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/sale-returns/{$saleReturnId}/credit-note")
            ->assertCreated()
            ->assertJsonPath('data.credit_note_number', 'NC-001-001-00000001-01')
            ->assertJsonPath('data.invoice_number_reference', '001-001-00000001')
            ->assertJsonPath('data.grand_total', 6.5);

        $this->assertDatabaseHas('credit_notes', [
            'sale_return_id' => $saleReturnId,
            'sale_id' => $saleId,
            'invoice_number_reference' => '001-001-00000001',
            'credit_note_number' => 'NC-001-001-00000001-01',
        ]);

        $this->assertDatabaseHas('credit_note_items', [
            'description' => 'Cheesecake premium',
            'line_total' => 6.5,
        ]);

        $this->postJson("/api/v1/sale-returns/{$saleReturnId}/credit-note")
            ->assertUnprocessable()
            ->assertJsonPath('errors.sale_return.0', 'Esta devolucion ya tiene una nota de credito emitida.');

        $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.invoice.items.0.description', 'Cheesecake premium')
            ->assertJsonPath('data.returns.0.credit_note.credit_note_number', 'NC-001-001-00000001-01');
    }

    public function test_credit_note_requires_invoiced_sale_and_admin_role(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $customer = Customer::query()->create([
            'public_id' => 'customer-credit-role',
            'name' => 'Cliente Rol Nota',
            'document_type' => 'ruc',
            'document_number' => '1790022222001',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Pie de limon',
            salePrice: 4,
            quantity: 5,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 15,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 4,
            'document_type' => 'ticket',
        ])->assertCreated()->json('data.id');

        $saleItemId = $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->json('data.items.0.id');

        $saleReturnId = $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'card',
            'reason' => 'Producto incorrecto.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated()
            ->json('data.id');

        $this->postJson("/api/v1/sale-returns/{$saleReturnId}/credit-note")
            ->assertForbidden();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/sale-returns/{$saleReturnId}/credit-note")
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.sale_return.0',
                'Solo las devoluciones de ventas facturadas pueden emitir nota de credito.',
            );
    }

    public function test_authenticated_user_can_download_invoice_pdf(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $customer = Customer::query()->create([
            'public_id' => 'customer-invoice-pdf',
            'name' => 'Cliente PDF Factura',
            'document_type' => 'ruc',
            'document_number' => '1790099999001',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Latte vainilla',
            salePrice: 5.75,
            quantity: 6,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 20,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 5.75,
            'document_type' => 'factura',
        ])->assertCreated();

        $invoice = Invoice::query()->firstOrFail();

        $response = $this->get("/api/v1/invoices/{$invoice->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="factura-001-001-00000001.pdf"');

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_authenticated_user_can_download_credit_note_pdf(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $customer = Customer::query()->create([
            'public_id' => 'customer-credit-note-pdf',
            'name' => 'Cliente PDF Nota Credito',
            'document_type' => 'ruc',
            'document_number' => '1790088888001',
            'is_active' => true,
        ]);

        $product = $this->createProductWithStock(
            name: 'Mocha helado',
            salePrice: 7.25,
            quantity: 7,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 25,
        ])->assertCreated();

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 7.25,
            'document_type' => 'factura',
        ])->assertCreated()->json('data.id');

        $saleItemId = $this->getJson("/api/v1/sales/{$saleId}")
            ->assertOk()
            ->json('data.items.0.id');

        $saleReturnId = $this->postJson("/api/v1/sales/{$saleId}/returns", [
            'refund_method' => 'card',
            'reason' => 'Cliente solicita acreditacion parcial.',
            'items' => [
                [
                    'sale_item_id' => $saleItemId,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/sale-returns/{$saleReturnId}/credit-note")
            ->assertCreated();

        Sanctum::actingAs($cashier);

        $creditNote = CreditNote::query()->firstOrFail();

        $response = $this->get("/api/v1/credit-notes/{$creditNote->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="nota-credito-NC-001-001-00000001-01.pdf"');

        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_checkout_with_factura_requires_identified_customer_and_active_resolution(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $product = $this->createProductWithStock(
            name: 'Mocaccino',
            salePrice: 6,
            quantity: 4,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 20,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 6,
            'document_type' => 'factura',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.document_type.0', 'Debes seleccionar un cliente para emitir factura.');

        $customer = Customer::query()->create([
            'public_id' => 'customer-no-resolution',
            'name' => 'Cliente Sin Resolucion',
            'document_type' => 'ruc',
            'document_number' => '1790099999001',
            'is_active' => true,
        ]);

        $this->patchJson('/api/v1/sales/draft', [
            'customer_id' => $customer->id,
        ])->assertOk();

        TaxResolution::query()->update(['is_active' => false]);

        $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 6,
            'document_type' => 'factura',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.document_type.0', 'No hay una dosificacion activa para emitir facturas.');
    }

    public function test_admin_can_cancel_completed_sale_and_reverse_stock_and_cash(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $product = $this->createProductWithStock(
            name: 'Tostada francesa',
            salePrice: 7,
            quantity: 5,
        );

        Sanctum::actingAs($cashier);

        $register = CashRegister::query()->firstOrFail();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 25,
        ])->assertCreated();

        $this->postJson('/api/v1/sales/draft/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $saleId = $this->postJson('/api/v1/sales', [
            'payment_method' => 'cash',
            'amount_paid' => 14,
            'document_type' => 'ticket',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/sales/{$saleId}/cancel", [
            'cancellation_reason' => 'Cliente desistio de la compra.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Cliente desistio de la compra.');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'status' => 'cancelled',
            'cancelled_by_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'type' => 'sale_return',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'type' => 'expense',
            'amount' => 14,
        ]);

        $currentStock = Product::query()
            ->withSum('stockMovements as current_stock', 'quantity')
            ->findOrFail($product->id)
            ->current_stock;

        $this->assertSame(5.0, (float) $currentStock);
        $cashIncome = (float) CashMovement::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $saleId)
            ->where('type', 'income')
            ->sum('amount');

        $cashExpense = (float) CashMovement::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $saleId)
            ->where('type', 'expense')
            ->sum('amount');

        $this->assertSame(14.0, $cashIncome);
        $this->assertSame(14.0, $cashExpense);
    }

    public function test_cashier_cannot_cancel_sales(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();

        $sale = Sale::query()->create([
            'public_id' => 'sale-cancel-test',
            'user_id' => $cashier->id,
            'status' => 'completed',
            'subtotal' => 10,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 10,
            'paid_total' => 10,
            'change_total' => 0,
            'sold_at' => now(),
        ]);

        Sanctum::actingAs($cashier);

        $this->postJson("/api/v1/sales/{$sale->id}/cancel", [
            'cancellation_reason' => 'No autorizado',
        ])->assertForbidden();
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
