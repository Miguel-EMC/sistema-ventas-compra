<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Company */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $admin = $this->resolveAdmin();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'plan' => $this->plan_id,
            'status' => $this->status,
            'registered_at' => $this->created_at?->toIso8601String(),
            'admin_email' => $admin?->email,
            'admin_username' => $admin?->username,
        ];
    }

    private function resolveAdmin(): ?User
    {
        if ($this->relationLoaded('users')) {
            return $this->users->first(fn (User $user): bool => $user->role?->slug === 'admin');
        }

        return $this->users()
            ->with('role')
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->oldest('created_at')
            ->first();
    }
}
