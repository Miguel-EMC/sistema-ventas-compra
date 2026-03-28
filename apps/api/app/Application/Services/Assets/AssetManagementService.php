<?php

namespace App\Application\Services\Assets;

use App\Models\Asset;
use Illuminate\Support\Str;

class AssetManagementService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Asset
    {
        return Asset::query()->create([
            'public_id' => (string) Str::uuid(),
            'code' => $payload['code'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'quantity' => $payload['quantity'],
            'acquisition_cost' => $payload['acquisition_cost'] ?? null,
            'acquired_at' => $payload['acquired_at'] ?? null,
            'status' => $payload['status'],
        ])->load('category');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Asset $asset, array $payload): Asset
    {
        $asset->update([
            'code' => $payload['code'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'quantity' => $payload['quantity'],
            'acquisition_cost' => $payload['acquisition_cost'] ?? null,
            'acquired_at' => $payload['acquired_at'] ?? null,
            'status' => $payload['status'],
        ]);

        return $asset->fresh('category');
    }
}
