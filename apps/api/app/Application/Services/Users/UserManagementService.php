<?php

namespace App\Application\Services\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param array{name:string,username:string,display_name?:string|null,email?:string|null,password:string,role_id:int,is_active?:bool,company_id?:string|null} $payload
     */
    public function create(array $payload, ?User $actor = null): User
    {
        return DB::transaction(function () use ($payload, $actor): User {
            $user = User::query()->create([
                'public_id' => (string) Str::uuid(),
                'company_id' => $payload['company_id'] ?? $actor?->company_id,
                'name' => $payload['name'],
                'username' => $payload['username'],
                'display_name' => $payload['display_name'] ?: $payload['name'],
                'email' => $payload['email'] ?: null,
                'password' => $payload['password'],
                'role_id' => $payload['role_id'],
                'is_active' => $payload['is_active'] ?? true,
            ]);

            return $user->load('role');
        });
    }

    /**
     * @param array{name:string,username:string,display_name?:string|null,email?:string|null,password?:string|null,role_id:int,is_active?:bool,company_id?:string|null} $payload
     */
    public function update(User $user, array $payload): User
    {
        return DB::transaction(function () use ($user, $payload): User {
            $this->guardLastAdminState($user, $payload);

            $attributes = [
                'company_id' => array_key_exists('company_id', $payload) ? $payload['company_id'] : $user->company_id,
                'name' => $payload['name'],
                'username' => $payload['username'],
                'display_name' => $payload['display_name'] ?: $payload['name'],
                'email' => $payload['email'] ?: null,
                'role_id' => $payload['role_id'],
                'is_active' => $payload['is_active'] ?? true,
            ];

            if (! empty($payload['password'])) {
                $attributes['password'] = $payload['password'];
            }

            $user->update($attributes);

            return $user->fresh('role');
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->guardLastAdminDeletion($user);
            $user->tokens()->delete();
            $user->delete();
        });
    }

    /**
     * @param array{role_id:int,is_active?:bool} $payload
     */
    private function guardLastAdminState(User $user, array $payload): void
    {
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->role?->slug !== 'admin') {
            return;
        }

        $nextRole = Role::query()->find($payload['role_id']);
        $willRemainAdmin = $nextRole?->slug === 'admin';
        $willRemainActive = $payload['is_active'] ?? true;

        if ($willRemainAdmin && $willRemainActive) {
            return;
        }

        $activeAdmins = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->when(
                $user->company_id === null,
                fn ($query) => $query->whereNull('company_id'),
                fn ($query) => $query->where('company_id', $user->company_id),
            )
            ->where('is_active', true)
            ->count();

        if ($activeAdmins <= 1) {
            throw ValidationException::withMessages([
                'role_id' => 'Debe existir al menos un administrador activo.',
            ]);
        }
    }

    private function guardLastAdminDeletion(User $user): void
    {
        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->role?->slug !== 'admin' || ! $user->is_active) {
            return;
        }

        $activeAdmins = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->when(
                $user->company_id === null,
                fn ($query) => $query->whereNull('company_id'),
                fn ($query) => $query->where('company_id', $user->company_id),
            )
            ->where('is_active', true)
            ->count();

        if ($activeAdmins <= 1) {
            throw ValidationException::withMessages([
                'user' => 'No se puede eliminar el ultimo administrador activo.',
            ]);
        }
    }
}
