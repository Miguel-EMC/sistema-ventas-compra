<?php

namespace App\Application\Services\Catalog;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductManagementService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, ?User $actor = null): Product
    {
        return DB::transaction(function () use ($payload, $actor): Product {
            $categoryId = $this->resolveCategoryId($payload['category_id'] ?? null);

            $product = Product::query()->create([
                'public_id' => (string) Str::uuid(),
                'sku' => $payload['sku'] ?? null,
                'barcode' => $payload['barcode'] ?? null,
                'category_id' => $categoryId,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'sale_price' => $payload['sale_price'],
                'cost_price' => $payload['cost_price'],
                'tax_rate' => $payload['tax_rate'] ?? 0,
                'unit' => $payload['unit'],
                'track_stock' => $payload['track_stock'] ?? true,
                'minimum_stock' => $payload['minimum_stock'] ?? 0,
                'is_active' => $payload['is_active'] ?? true,
            ]);

            $initialStock = (float) ($payload['initial_stock'] ?? 0);

            if ($product->track_stock && $initialStock !== 0.0) {
                $this->recordStockMovement(
                    product: $product,
                    actor: $actor,
                    quantity: $initialStock,
                    type: 'opening_balance',
                    reason: 'initial_stock',
                    notes: 'Saldo inicial del producto',
                );
            }

            return $product->load('category');
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Product $product, array $payload): Product
    {
        $categoryId = $this->resolveCategoryId($payload['category_id'] ?? null);

        $product->update([
            'sku' => $payload['sku'] ?? null,
            'barcode' => $payload['barcode'] ?? null,
            'category_id' => $categoryId,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'sale_price' => $payload['sale_price'],
            'cost_price' => $payload['cost_price'],
            'tax_rate' => $payload['tax_rate'] ?? 0,
            'unit' => $payload['unit'],
            'track_stock' => $payload['track_stock'] ?? true,
            'minimum_stock' => $payload['minimum_stock'] ?? 0,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return $product->fresh('category');
    }

    private function resolveCategoryId(mixed $categoryId): ?int
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        return ProductCategory::query()->findOrFail((int) $categoryId)->id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function adjustStock(Product $product, array $payload, ?User $actor = null): Product
    {
        return DB::transaction(function () use ($product, $payload, $actor): Product {
            $quantity = (float) $payload['quantity'];

            $this->recordStockMovement(
                product: $product,
                actor: $actor,
                quantity: $quantity,
                type: $quantity >= 0 ? 'adjustment_in' : 'adjustment_out',
                reason: (string) ($payload['reason'] ?? 'manual_adjustment'),
                notes: $payload['notes'] ?? null,
            );

            return $product->fresh('category');
        });
    }

    private function recordStockMovement(
        Product $product,
        ?User $actor,
        float $quantity,
        string $type,
        ?string $reason,
        ?string $notes,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'reason' => $reason,
            'quantity' => $quantity,
            'unit_cost' => $product->cost_price,
            'notes' => $notes,
            'occurred_at' => now(),
        ]);
    }
}
