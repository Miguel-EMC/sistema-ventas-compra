<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\AssetCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoreReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Acceso total a la plataforma',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ventas',
                'slug' => 'cashier',
                'description' => 'Operacion diaria de caja y ventas',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('currencies')->insertOrIgnore([
            [
                'name' => 'Dolar estadounidense',
                'code' => 'USD',
                'symbol' => '$',
                'decimals' => 2,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peso argentino',
                'code' => 'ARS',
                'symbol' => '$',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Boliviano',
                'code' => 'BOB',
                'symbol' => 'Bs.',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Real brasileño',
                'code' => 'BRL',
                'symbol' => 'R$',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peso chileno',
                'code' => 'CLP',
                'symbol' => '$',
                'decimals' => 0,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peso colombiano',
                'code' => 'COP',
                'symbol' => '$',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peso mexicano',
                'code' => 'MXN',
                'symbol' => '$',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Guarani paraguayo',
                'code' => 'PYG',
                'symbol' => '₲',
                'decimals' => 0,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sol peruano',
                'code' => 'PEN',
                'symbol' => 'S/',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Peso uruguayo',
                'code' => 'UYU',
                'symbol' => '$',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bolivar venezolano',
                'code' => 'VES',
                'symbol' => 'Bs.',
                'decimals' => 2,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('locales')->insertOrIgnore([
            [
                'name' => 'Español',
                'code' => 'es-EC',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'English',
                'code' => 'en-US',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Português',
                'code' => 'pt-BR',
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        if (!DB::table('company_profiles')->where('is_primary', true)->exists()) {
            DB::table('company_profiles')->insert([
                'legal_name' => 'VentasPOS Demo',
                'trade_name' => 'VentasPOS',
                'tax_id' => '0999999999001',
                'email' => 'admin@ventaspos.local',
                'phone' => '0990000000',
                'website' => 'https://ventaspos.local',
                'address_line' => 'Av. Principal 100',
                'city' => 'Guayaquil',
                'region' => 'Guayas',
                'country_code' => 'EC',
                'is_primary' => true,
                'metadata' => json_encode([
                    'billing_owner_name' => 'Administrador base',
                    'billing_address_reference' => 'Matriz',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            'currency_code' => 'USD',
            'locale_code' => 'es-EC',
            'timezone' => 'America/Guayaquil',
            'tax_included_prices' => false,
            'allow_negative_stock' => false,
            'default_document_type' => 'ticket',
            'invoice_footer' => 'Gracias por su compra.',
        ] as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'group' => match ($key) {
                        'tax_included_prices', 'default_document_type', 'invoice_footer' => 'sales',
                        'allow_negative_stock' => 'stock',
                        default => 'general',
                    },
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('tax_resolutions')->where('authorization_number', '!=', 'AUTH-DEMO-001')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        $companyProfileId = DB::table('company_profiles')->where('is_primary', true)->value('id');

        DB::table('tax_resolutions')->updateOrInsert(
            ['authorization_number' => 'AUTH-DEMO-001'],
            [
                'company_profile_id' => $companyProfileId,
                'name' => 'Dosificacion demo principal',
                'series' => '001-001',
                'invoice_number_start' => 1,
                'invoice_number_end' => 99999999,
                'next_invoice_number' => 1,
                'starts_at' => now()->startOfYear(),
                'ends_at' => now()->addYear()->endOfYear(),
                'technical_key' => 'DEMO-KEY-VENTASPOS',
                'legend' => 'Documento emitido en la plataforma nueva.',
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        User::query()->updateOrCreate(
            ['email' => 'admin@ventaspos.local'],
            [
                'public_id' => (string) Str::uuid(),
                'name' => 'Administrador base',
                'username' => 'admin',
                'display_name' => 'Administrador',
                'password' => Hash::make('password'),
                'role_id' => $adminRoleId,
                'is_active' => true,
            ],
        );

        ProductCategory::query()->firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => 'Categoria base del catalogo comercial',
                'is_active' => true,
            ],
        );

        AssetCategory::query()->firstOrCreate(
            ['slug' => 'operativo'],
            [
                'name' => 'Operativo',
                'description' => 'Categoria base para activos internos',
            ],
        );

        DB::table('cash_registers')->insertOrIgnore([
            [
                'public_id' => (string) Str::uuid(),
                'name' => 'Caja principal',
                'code' => 'CJ-01',
                'location' => 'Mostrador principal',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
