<?php

namespace App\Application\Services\Reports;

use App\Models\Asset;
use App\Models\Product;

class CatalogExportService
{
    /**
     * @return array<string, mixed>
     */
    public function productCatalog(?string $search = null): array
    {
        $normalizedSearch = $this->normalizeSearch($search);

        $products = Product::query()
            ->with('category')
            ->withSum('stockMovements as current_stock', 'quantity')
            ->when($normalizedSearch !== null, function ($query) use ($normalizedSearch): void {
                $query->where(function ($innerQuery) use ($normalizedSearch): void {
                    $innerQuery
                        ->where('name', 'like', "%{$normalizedSearch}%")
                        ->orWhere('sku', 'like', "%{$normalizedSearch}%")
                        ->orWhere('barcode', 'like', "%{$normalizedSearch}%");
                });
            })
            ->orderBy('name')
            ->get();

        $items = $products->map(function (Product $product): array {
            $currentStock = (float) ($product->current_stock ?? 0);
            $minimumStock = (float) $product->minimum_stock;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category_name' => $product->category?->name,
                'sale_price' => (float) $product->sale_price,
                'cost_price' => (float) $product->cost_price,
                'unit' => $product->unit,
                'track_stock' => (bool) $product->track_stock,
                'current_stock' => $currentStock,
                'minimum_stock' => $minimumStock,
                'is_active' => (bool) $product->is_active,
                'is_low_stock' => (bool) $product->track_stock && $currentStock <= $minimumStock,
                'updated_at' => $product->updated_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'search' => $normalizedSearch,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'products_count' => count($items),
                'tracked_count' => count(array_filter(
                    $items,
                    fn (array $item): bool => (bool) ($item['track_stock'] ?? false),
                )),
                'active_count' => count(array_filter(
                    $items,
                    fn (array $item): bool => (bool) ($item['is_active'] ?? false),
                )),
                'low_stock_count' => count(array_filter(
                    $items,
                    fn (array $item): bool => (bool) ($item['is_low_stock'] ?? false),
                )),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assetCatalog(?string $search = null): array
    {
        $normalizedSearch = $this->normalizeSearch($search);

        $assets = Asset::query()
            ->with('category')
            ->when($normalizedSearch !== null, function ($query) use ($normalizedSearch): void {
                $query->where(function ($innerQuery) use ($normalizedSearch): void {
                    $innerQuery
                        ->where('name', 'like', "%{$normalizedSearch}%")
                        ->orWhere('code', 'like', "%{$normalizedSearch}%")
                        ->orWhere('status', 'like', "%{$normalizedSearch}%");
                });
            })
            ->orderBy('name')
            ->get();

        $items = $assets->map(function (Asset $asset): array {
            return [
                'name' => $asset->name,
                'code' => $asset->code,
                'category_name' => $asset->category?->name,
                'quantity' => (float) $asset->quantity,
                'acquisition_cost' => $asset->acquisition_cost !== null ? (float) $asset->acquisition_cost : null,
                'acquired_at' => $asset->acquired_at?->toDateString(),
                'status' => $asset->status,
                'updated_at' => $asset->updated_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'search' => $normalizedSearch,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'assets_count' => count($items),
                'active_count' => count(array_filter(
                    $items,
                    fn (array $item): bool => ($item['status'] ?? null) === 'active',
                )),
                'total_units' => array_reduce(
                    $items,
                    fn (float $carry, array $item): float => $carry + (float) ($item['quantity'] ?? 0),
                    0.0,
                ),
            ],
            'items' => $items,
        ];
    }

    private function normalizeSearch(?string $search): ?string
    {
        $normalized = trim((string) $search);

        return $normalized === '' ? null : $normalized;
    }
}
