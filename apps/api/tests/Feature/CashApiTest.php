<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_view_and_close_cash_session(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $register = CashRegister::query()->firstOrFail();

        Sanctum::actingAs($cashier);

        $this->getJson('/api/v1/cash/registers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $register->id);

        $openResponse = $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 50,
            'notes' => 'Inicio de turno',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $sessionId = $openResponse->json('data.id');

        $this->getJson('/api/v1/cash/sessions/current')
            ->assertOk()
            ->assertJsonPath('data.id', $sessionId);

        $this->postJson("/api/v1/cash/sessions/{$sessionId}/close", [
            'closing_amount' => 75,
            'notes' => 'Cierre de turno',
        ])->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.closing_amount', 75);

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $sessionId,
            'status' => 'closed',
        ]);
    }

    public function test_cashier_cannot_open_two_cash_sessions_at_once(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $register = CashRegister::query()->firstOrFail();

        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 30,
        ])->assertCreated();

        $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 40,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.session.0', 'Ya existe una caja abierta para este usuario.');
    }

    private function createCashier(): User
    {
        $cashierRoleId = Role::query()->where('slug', 'cashier')->value('id');

        return User::query()->create([
            'public_id' => 'cashier-cash',
            'name' => 'Usuario Caja',
            'username' => 'caja-activa',
            'display_name' => 'Caja Activa',
            'email' => 'caja-activa@example.com',
            'password' => 'password123',
            'role_id' => $cashierRoleId,
            'is_active' => true,
        ]);
    }
}
