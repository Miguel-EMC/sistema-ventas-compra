<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompaniesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_create_companies(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::query()->where('username', 'superadmin')->firstOrFail();
        Sanctum::actingAs($superadmin);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.domain', 'demo');

        $response = $this->postJson('/api/v1/companies', [
            'name' => 'Casa Central Foods',
            'domain' => 'casa-central',
            'plan' => 'pro',
            'admin_email' => 'admin@casacentral.ec',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.company.name', 'Casa Central Foods')
            ->assertJsonPath('data.company.domain', 'casa-central')
            ->assertJsonPath('data.company.plan', 'pro')
            ->assertJsonPath('data.company.status', 'pending')
            ->assertJsonPath('data.company.admin_email', 'admin@casacentral.ec')
            ->assertJsonPath('data.company.admin_username', 'casa-central-admin');

        $this->assertDatabaseHas('companies', [
            'name' => 'Casa Central Foods',
            'domain' => 'casa-central',
            'plan_id' => 'pro',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'casa-central-admin',
            'email' => 'admin@casacentral.ec',
        ]);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonFragment(['domain' => 'casa-central']);
    }

    public function test_company_creation_returns_temporary_credentials(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $superadmin */
        $superadmin = User::query()->where('username', 'superadmin')->firstOrFail();
        Sanctum::actingAs($superadmin);

        $response = $this->postJson('/api/v1/companies', [
            'name' => 'Orbita Atelier',
            'domain' => 'orbita-atelier',
            'plan' => 'basic',
            'admin_email' => 'founder@orbitaatelier.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.provisioning.admin_email', 'founder@orbitaatelier.com')
            ->assertJsonPath('data.provisioning.admin_username', 'orbita-atelier-admin');

        $this->assertIsString($response->json('data.provisioning.temporary_password'));
        $this->assertGreaterThanOrEqual(10, strlen((string) $response->json('data.provisioning.temporary_password')));
    }

    public function test_admin_cannot_manage_companies(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        /** @var User $admin */
        $admin = User::query()->where('username', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/companies')->assertForbidden();
        $this->postJson('/api/v1/companies', [
            'name' => 'Demo',
            'domain' => 'demo',
            'plan' => 'basic',
            'admin_email' => 'demo@example.com',
        ])->assertForbidden();
    }
}
