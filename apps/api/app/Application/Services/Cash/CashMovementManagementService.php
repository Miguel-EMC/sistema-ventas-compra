<?php

namespace App\Application\Services\Cash;

use App\Models\CashMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashMovementManagementService
{
    private const MANUAL_REFERENCE_TYPE = 'manual_cash_movement';

    public function __construct(
        private readonly CashSessionService $cashSessionService,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, CashMovement>
     */
    public function list(User $user, array $filters = []): Collection
    {
        $type = trim((string) ($filters['type'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        return CashMovement::query()
            ->with(['session.register', 'user'])
            ->where('reference_type', self::MANUAL_REFERENCE_TYPE)
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('category', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when(! $this->isAdmin($user), function ($query) use ($user): void {
                $query->where(function ($innerQuery) use ($user): void {
                    $innerQuery
                        ->where('user_id', $user->id)
                        ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('opened_by_id', $user->id));
                });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(User $user, array $payload): CashMovement
    {
        $session = $this->cashSessionService->getCurrentSession($user);

        if ($session === null) {
            throw ValidationException::withMessages([
                'cash_session' => 'Debes abrir una caja antes de registrar movimientos manuales.',
            ]);
        }

        return DB::transaction(function () use ($user, $payload, $session): CashMovement {
            $movement = CashMovement::query()->create([
                'public_id' => (string) Str::uuid(),
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'type' => trim((string) $payload['type']),
                'category' => $this->normalizeCategory($payload['category'] ?? null),
                'amount' => round((float) $payload['amount'], 2),
                'reference_type' => self::MANUAL_REFERENCE_TYPE,
                'reference_id' => null,
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'occurred_at' => $this->parseDateTime($payload['occurred_at'] ?? null) ?? now(),
            ]);

            return $this->loadRelations($movement);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(CashMovement $movement, User $user, array $payload): CashMovement
    {
        $movement = $this->loadRelations($movement);
        $this->guardEditable($movement, $user);

        return DB::transaction(function () use ($movement, $payload): CashMovement {
            $movement->update([
                'type' => trim((string) $payload['type']),
                'category' => $this->normalizeCategory($payload['category'] ?? null),
                'amount' => round((float) $payload['amount'], 2),
                'notes' => $this->nullableText($payload['notes'] ?? null),
                'occurred_at' => $this->parseDateTime($payload['occurred_at'] ?? null) ?? $movement->occurred_at ?? now(),
            ]);

            return $this->loadRelations($movement->fresh());
        });
    }

    public function delete(CashMovement $movement, User $user): void
    {
        $movement = $this->loadRelations($movement);
        $this->guardEditable($movement, $user);
        $movement->delete();
    }

    public function loadRelations(CashMovement $movement): CashMovement
    {
        return $movement->load([
            'session.register',
            'user',
        ]);
    }

    private function guardEditable(CashMovement $movement, User $user): void
    {
        if ($movement->reference_type !== self::MANUAL_REFERENCE_TYPE) {
            throw ValidationException::withMessages([
                'movement' => 'Solo puedes modificar movimientos manuales.',
            ]);
        }

        $session = $movement->session;

        if ($session === null || $session->status !== 'open') {
            throw ValidationException::withMessages([
                'movement' => 'Solo puedes modificar movimientos de una caja abierta.',
            ]);
        }

        if ($session->opened_by_id !== $user->id && ! $this->isAdmin($user)) {
            throw ValidationException::withMessages([
                'movement' => 'No puedes modificar movimientos de otra caja.',
            ]);
        }
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role?->slug, ['admin', 'superadmin'], true);
    }

    private function normalizeCategory(mixed $value): string
    {
        return Str::slug(trim((string) $value), '_');
    }

    private function nullableText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
