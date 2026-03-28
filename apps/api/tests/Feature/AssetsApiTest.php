<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_and_delete_assets(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $categoryResponse = $this->postJson('/api/v1/asset-categories', [
            'name' => 'Tecnologia',
            'description' => 'Equipos y perifericos',
        ])->assertCreated();

        $categoryId = $categoryResponse->json('data.id');

        $assetResponse = $this->postJson('/api/v1/assets', [
            'name' => 'Impresora termica',
            'code' => 'IMP-01',
            'category_id' => $categoryId,
            'description' => 'Equipo para tickets',
            'quantity' => 2,
            'acquisition_cost' => 180,
            'acquired_at' => '2026-03-01',
            'status' => 'active',
        ])->assertCreated();

        $assetId = $assetResponse->json('data.id');

        $this->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Impresora termica');

        $this->patchJson("/api/v1/assets/{$assetId}", [
            'name' => 'Impresora fiscal',
            'code' => 'IMP-01',
            'category_id' => $categoryId,
            'description' => 'Equipo para tickets',
            'quantity' => 1,
            'acquisition_cost' => 195,
            'acquired_at' => '2026-03-02',
            'status' => 'maintenance',
        ])->assertOk()
            ->assertJsonPath('data.status', 'maintenance');

        $this->deleteJson("/api/v1/assets/{$assetId}")
            ->assertOk();

        $this->assertSoftDeleted('assets', [
            'id' => $assetId,
        ]);
    }

    public function test_authenticated_users_can_list_assets(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $category = AssetCategory::query()->firstOrFail();

        Asset::query()->create([
            'public_id' => 'asset-demo',
            'name' => 'Laptop demo',
            'code' => 'LP-01',
            'category_id' => $category->id,
            'quantity' => 1,
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Laptop demo');
    }
}
