<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_records_from_its_company_across_core_modules(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $demoAdmin */
        $demoAdmin = User::query()->where('username', 'admin')->firstOrFail();
        $otherAdmin = $this->createTenantAdmin('tenant-dos');

        Sanctum::actingAs($demoAdmin);
        $demoData = $this->createCompanyDataset($demoAdmin);

        Sanctum::actingAs($otherAdmin);
        $this->createCompanyDataset($otherAdmin);

        Sanctum::actingAs($demoAdmin);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $demoData['product']->id);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $demoData['customer']->id);

        $this->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $demoData['supplier']->id);

        $this->getJson('/api/v1/cash/registers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $demoData['cash_register']->id]);

        $this->getJson('/api/v1/purchase-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $demoData['purchase_order']->id);

        $this->getJson('/api/v1/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $demoData['sale']->id);
    }

    public function test_superadmin_can_see_cross_company_records_for_global_support(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::withoutGlobalScopes()->where('username', 'superadmin')->firstOrFail();
        /** @var User $demoAdmin */
        $demoAdmin = User::withoutGlobalScopes()->where('username', 'admin')->firstOrFail();
        $otherAdmin = $this->createTenantAdmin('tenant-tres');

        Sanctum::actingAs($demoAdmin);
        $this->createCompanyDataset($demoAdmin);

        Sanctum::actingAs($otherAdmin);
        $this->createCompanyDataset($otherAdmin);

        Sanctum::actingAs($superadmin);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/customers')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/suppliers')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/cash/registers')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/purchase-orders')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/sales')->assertOk()->assertJsonCount(2, 'data');
    }

    /**
     * @return array{
     *   product: Product,
     *   customer: Customer,
     *   supplier: Supplier,
     *   cash_register: CashRegister,
     *   purchase_order: PurchaseOrder,
     *   sale: Sale
     * }
     */
    private function createCompanyDataset(User $admin): array
    {
        $category = ProductCategory::query()->create([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
            'description' => 'Categoria compartida',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'public_id' => (string) Str::uuid(),
            'category_id' => $category->id,
            'name' => 'Cafe en grano',
            'sku' => 'SKU-001',
            'barcode' => 'BAR-001',
            'sale_price' => 12.50,
            'cost_price' => 8.10,
            'tax_rate' => 0,
            'unit' => 'unidad',
            'track_stock' => true,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'public_id' => (string) Str::uuid(),
            'document_number' => 'DOC-001',
            'name' => 'Cliente Demo',
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'public_id' => (string) Str::uuid(),
            'document_number' => 'SUP-001',
            'name' => 'Proveedor Demo',
            'is_active' => true,
        ]);

        $cashRegister = CashRegister::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Caja secundaria',
            'code' => 'CJ-02',
            'location' => 'Mostrador',
            'is_active' => true,
        ]);

        $purchaseOrder = PurchaseOrder::query()->create([
            'company_id' => $admin->company_id,
            'public_id' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'status' => 'ordered',
            'subtotal' => 25,
            'tax_total' => 0,
            'grand_total' => 25,
            'ordered_at' => now(),
        ]);

        $sale = Sale::query()->create([
            'company_id' => $admin->company_id,
            'public_id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'status' => 'completed',
            'subtotal' => 12.5,
            'tax_total' => 0,
            'discount_total' => 0,
            'grand_total' => 12.5,
            'paid_total' => 12.5,
            'change_total' => 0,
            'sold_at' => now(),
        ]);

        return [
            'product' => $product,
            'customer' => $customer,
            'supplier' => $supplier,
            'cash_register' => $cashRegister,
            'purchase_order' => $purchaseOrder,
            'sale' => $sale,
        ];
    }

    private function createTenantAdmin(string $domain): User
    {
        $company = Company::query()->create([
            'name' => strtoupper($domain),
            'domain' => $domain,
            'status' => 'active',
            'plan_id' => 'pro',
        ]);

        $adminRoleId = Role::query()->where('slug', 'admin')->value('id');

        return User::withoutGlobalScopes()->create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'name' => 'Admin '.$domain,
            'username' => $domain.'-admin',
            'display_name' => 'Admin '.strtoupper($domain),
            'email' => $domain.'@example.com',
            'password' => 'password',
            'role_id' => $adminRoleId,
            'is_active' => true,
        ]);
    }
}
