<?php

namespace App\Application\Services\Catalog;

use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategoryManagementService
{
    /**
     * @param array{name:string,description?:string|null,is_active?:bool} $payload
     */
    public function create(array $payload): ProductCategory
    {
        return ProductCategory::query()->create([
            'name' => $payload['name'],
            'slug' => $this->buildUniqueSlug($payload['name']),
            'description' => $payload['description'] ?: null,
            'is_active' => $payload['is_active'] ?? true,
        ]);
    }

    /**
     * @param array{name:string,description?:string|null,is_active?:bool} $payload
     */
    public function update(ProductCategory $category, array $payload): ProductCategory
    {
        $category->update([
            'name' => $payload['name'],
            'slug' => $this->buildUniqueSlug($payload['name'], $category->id),
            'description' => $payload['description'] ?: null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return $category->fresh();
    }

    private function buildUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'categoria';
        $slug = $base;
        $counter = 1;

        while (
            ProductCategory::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $counter++;
            $slug = "{$base}-{$counter}";
        }

        return $slug;
    }
}
