<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canManage = false;

        if ($user !== null && $this->reference_type === 'manual_cash_movement') {
            $isAdmin = $user->role?->slug === 'admin';
            $canManage = $this->session?->status === 'open'
                && ($this->session?->opened_by_id === $user->id || $isAdmin);
        }

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'type' => $this->type,
            'category' => $this->category,
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'can_manage' => $canManage,
            'cash_session' => $this->whenLoaded('session', function (): ?array {
                if ($this->session === null) {
                    return null;
                }

                return [
                    'id' => $this->session->id,
                    'status' => $this->session->status,
                    'register_name' => $this->session->register?->name,
                    'opened_at' => $this->session->opened_at?->toIso8601String(),
                ];
            }),
            'user' => $this->whenLoaded('user', function (): ?array {
                if ($this->user === null) {
                    return null;
                }

                return [
                    'id' => $this->user->id,
                    'name' => $this->user->display_name ?: $this->user->name,
                    'username' => $this->user->username,
                ];
            }),
        ];
    }
}
