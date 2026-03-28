<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_business_settings(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'settings-cashier-id',
            'name' => 'Usuario Ventas',
            'username' => 'ventas-settings',
            'display_name' => 'Ventas Settings',
            'email' => 'ventas-settings@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/settings/business')
            ->assertOk()
            ->assertJsonPath('data.company_profile.legal_name', 'VentasPOS Demo')
            ->assertJsonPath('data.currency.code', 'USD')
            ->assertJsonPath('data.locale.code', 'es-EC')
            ->assertJsonPath('data.system_settings.default_document_type', 'ticket');
    }

    public function test_admin_can_update_business_settings(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/settings/business', [
            'company_profile' => [
                'legal_name' => 'Cafe Central S.A.S.',
                'trade_name' => 'Cafe Central',
                'tax_id' => '1790012345001',
                'email' => 'hola@cafecentral.test',
                'phone' => '0999999999',
                'website' => 'https://cafecentral.test',
                'address_line' => 'Av. Principal 123',
                'city' => 'Quito',
                'region' => 'Pichincha',
                'country_code' => 'ec',
            ],
            'system_settings' => [
                'currency_code' => 'USD',
                'locale_code' => 'es-EC',
                'timezone' => 'America/Guayaquil',
                'tax_included_prices' => true,
                'allow_negative_stock' => false,
                'default_document_type' => 'factura',
                'invoice_footer' => 'Gracias por su compra.',
            ],
        ])->assertOk()
            ->assertJsonPath('data.company_profile.legal_name', 'Cafe Central S.A.S.')
            ->assertJsonPath('data.company_profile.country_code', 'EC')
            ->assertJsonPath('data.system_settings.tax_included_prices', true)
            ->assertJsonPath('data.system_settings.default_document_type', 'factura')
            ->assertJsonPath('data.system_settings.invoice_footer', 'Gracias por su compra.');

        $this->assertDatabaseHas('company_profiles', [
            'legal_name' => 'Cafe Central S.A.S.',
            'trade_name' => 'Cafe Central',
            'country_code' => 'EC',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'default_document_type',
        ]);
    }

    public function test_cashier_cannot_update_business_settings(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        $cashier = User::query()->create([
            'public_id' => 'settings-cashier-blocked',
            'name' => 'Usuario Ventas',
            'username' => 'ventas-blocked',
            'display_name' => 'Ventas Blocked',
            'email' => 'ventas-blocked@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);

        Sanctum::actingAs($cashier);

        $this->putJson('/api/v1/settings/business', [
            'company_profile' => [
                'legal_name' => 'No autorizado',
            ],
            'system_settings' => [
                'currency_code' => 'USD',
                'locale_code' => 'es-EC',
                'timezone' => 'America/Guayaquil',
                'tax_included_prices' => false,
                'allow_negative_stock' => false,
                'default_document_type' => 'ticket',
            ],
        ])->assertForbidden();
    }
}
