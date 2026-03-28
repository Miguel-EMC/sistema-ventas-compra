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
        ]);

        DB::table('locales')->insertOrIgnore([
            [
                'name' => 'Español',
                'code' => 'es-EC',
                'is_default' => true,
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
                'metadata' => null,
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
