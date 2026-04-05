<?php

namespace App\Application\Services\Cash;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashSessionService
{
    public function getCurrentSession(?User $user): ?CashSession
    {
        if (! $user instanceof User) {
            return null;
        }

        $session = CashSession::query()
            ->where('opened_by_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session instanceof CashSession) {
            return null;
        }

        return $this->loadSessionMetrics($session);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function open(User $user, array $payload): CashSession
    {
        if ($this->getCurrentSession($user) instanceof CashSession) {
            throw ValidationException::withMessages([
                'session' => 'Ya existe una caja abierta para este usuario.',
            ]);
        }

        /** @var CashRegister $register */
        $register = CashRegister::query()->findOrFail($payload['cash_register_id']);

        if (! $register->is_active) {
            throw ValidationException::withMessages([
                'cash_register_id' => 'Solo se puede abrir una caja activa.',
            ]);
        }

        return DB::transaction(function () use ($user, $payload, $register): CashSession {
            $session = CashSession::query()->create([
                'public_id' => (string) Str::uuid(),
                'cash_register_id' => $register->id,
                'opened_by_id' => $user->id,
                'opened_at' => now(),
                'opening_amount' => $payload['opening_amount'],
                'status' => 'open',
                'notes' => $payload['notes'] ?? null,
            ]);

            CashMovement::query()->create([
                'public_id' => (string) Str::uuid(),
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'type' => 'opening',
                'category' => 'opening',
                'amount' => $payload['opening_amount'],
                'notes' => 'Apertura de caja',
                'occurred_at' => now(),
            ]);

            return $this->loadSessionMetrics($session);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function close(User $user, CashSession $session, array $payload): CashSession
    {
        if ($session->status !== 'open') {
            throw ValidationException::withMessages([
                'session' => 'La caja seleccionada ya fue cerrada.',
            ]);
        }

        if ($session->opened_by_id !== $user->id && ! in_array($user->role?->slug, ['admin', 'superadmin'], true)) {
            throw ValidationException::withMessages([
                'session' => 'No puedes cerrar una caja abierta por otro usuario.',
            ]);
        }

        return DB::transaction(function () use ($user, $session, $payload): CashSession {
            $session->update([
                'closed_by_id' => $user->id,
                'closed_at' => now(),
                'closing_amount' => $payload['closing_amount'],
                'status' => 'closed',
                'notes' => $payload['notes'] ?? $session->notes,
            ]);

            CashMovement::query()->create([
                'public_id' => (string) Str::uuid(),
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'type' => 'closing',
                'category' => 'closing',
                'amount' => $payload['closing_amount'],
                'notes' => 'Cierre de caja',
                'occurred_at' => now(),
            ]);

            return $this->loadSessionMetrics($session->fresh());
        });
    }

    /**
     * @return Collection<int, CashRegister>
     */
    public function availableRegisters(): Collection
    {
        return CashRegister::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, CashSession>
     */
    public function recentSessions(): Collection
    {
        return CashSession::query()
            ->with(['register', 'openedBy', 'closedBy'])
            ->withCount(['sales' => fn ($query) => $query->where('status', 'completed')])
            ->withSum(['sales as sales_total' => fn ($query) => $query->where('status', 'completed')], 'grand_total')
            ->withSum(['movements as cash_income_total' => fn ($query) => $query->where('type', 'income')], 'amount')
            ->withSum(['movements as cash_out_total' => fn ($query) => $query->where('type', 'expense')], 'amount')
            ->orderByDesc('opened_at')
            ->limit(20)
            ->get();
    }

    private function loadSessionMetrics(CashSession $session): CashSession
    {
        return $session->load(['register', 'openedBy', 'closedBy'])
            ->loadCount(['sales' => fn ($query) => $query->where('status', 'completed')])
            ->loadSum(['sales as sales_total' => fn ($query) => $query->where('status', 'completed')], 'grand_total')
            ->loadSum(['movements as cash_income_total' => fn ($query) => $query->where('type', 'income')], 'amount')
            ->loadSum(['movements as cash_out_total' => fn ($query) => $query->where('type', 'expense')], 'amount');
    }
}
