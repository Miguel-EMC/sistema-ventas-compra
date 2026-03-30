<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\CashMovement;
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

    public function test_cashier_can_register_update_list_and_delete_manual_cash_movements(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $register = CashRegister::query()->firstOrFail();

        Sanctum::actingAs($cashier);

        $sessionId = $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 80,
            'notes' => 'Turno con movimientos manuales',
        ])->assertCreated()->json('data.id');

        $movementId = $this->postJson('/api/v1/cash/movements', [
            'type' => 'expense',
            'category' => 'operational_expense',
            'amount' => 12.5,
            'notes' => 'Pago de taxi para entrega',
            'occurred_at' => '2026-03-28 18:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.category', 'operational_expense')
            ->assertJsonPath('data.amount', 12.5)
            ->assertJsonPath('data.cash_session.id', $sessionId)
            ->assertJsonPath('data.can_manage', true)
            ->json('data.id');

        $this->assertDatabaseHas('cash_movements', [
            'id' => $movementId,
            'type' => 'expense',
            'category' => 'operational_expense',
            'reference_type' => 'manual_cash_movement',
            'amount' => 12.5,
        ]);

        $this->getJson('/api/v1/cash/movements')
            ->assertOk()
            ->assertJsonPath('data.0.id', $movementId);

        $this->patchJson("/api/v1/cash/movements/{$movementId}", [
            'type' => 'income',
            'category' => 'other_income',
            'amount' => 15,
            'notes' => 'Reembolso de caja menor',
            'occurred_at' => '2026-03-28 18:10:00',
        ])->assertOk()
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount', 15)
            ->assertJsonPath('data.category', 'other_income');

        $this->assertDatabaseHas('cash_movements', [
            'id' => $movementId,
            'type' => 'income',
            'category' => 'other_income',
            'amount' => 15,
        ]);

        $this->deleteJson("/api/v1/cash/movements/{$movementId}")
            ->assertOk();

        $this->assertDatabaseMissing('cash_movements', [
            'id' => $movementId,
        ]);
    }

    public function test_manual_cash_movement_requires_open_session_and_cannot_edit_system_movements_or_closed_sessions(): void
    {
        $this->seed(CoreReferenceSeeder::class);

        $cashier = $this->createCashier();
        $register = CashRegister::query()->firstOrFail();

        Sanctum::actingAs($cashier);

        $this->postJson('/api/v1/cash/movements', [
            'type' => 'expense',
            'category' => 'services',
            'amount' => 8,
            'notes' => 'Internet',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cash_session.0', 'Debes abrir una caja antes de registrar movimientos manuales.');

        $sessionId = $this->postJson('/api/v1/cash/sessions', [
            'cash_register_id' => $register->id,
            'opening_amount' => 40,
        ])->assertCreated()->json('data.id');

        $systemMovement = CashMovement::query()->create([
            'public_id' => 'cash-sale-system',
            'cash_session_id' => $sessionId,
            'user_id' => $cashier->id,
            'type' => 'income',
            'category' => 'sale',
            'amount' => 25,
            'reference_type' => 'sale',
            'reference_id' => 10,
            'notes' => 'Movimiento generado por venta',
            'occurred_at' => now(),
        ]);

        $this->patchJson("/api/v1/cash/movements/{$systemMovement->id}", [
            'type' => 'expense',
            'category' => 'misc',
            'amount' => 5,
            'notes' => 'No permitido',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.movement.0', 'Solo puedes modificar movimientos manuales.');

        $manualMovementId = $this->postJson('/api/v1/cash/movements', [
            'type' => 'expense',
            'category' => 'supplies',
            'amount' => 6,
            'notes' => 'Compra de cinta',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/cash/sessions/{$sessionId}/close", [
            'closing_amount' => 34,
        ])->assertOk();

        $this->patchJson("/api/v1/cash/movements/{$manualMovementId}", [
            'type' => 'expense',
            'category' => 'supplies',
            'amount' => 7,
            'notes' => 'Intento despues del cierre',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.movement.0', 'Solo puedes modificar movimientos de una caja abierta.');
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
