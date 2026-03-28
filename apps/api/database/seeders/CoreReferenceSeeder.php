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
    }
}
