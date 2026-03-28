<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'acquisition_cost' => $this->acquisition_cost !== null ? (float) $this->acquisition_cost : null,
            'acquired_at' => $this->acquired_at?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'category' => $this->whenLoaded('category', fn () => new AssetCategoryResource($this->category)),
        ];
    }
}
