<?php

namespace App\Application\Services\Assets;

use App\Models\AssetCategory;
use Illuminate\Support\Str;

class AssetCategoryManagementService
{
    /**
     * @param array{name:string,description?:string|null} $payload
     */
    public function create(array $payload): AssetCategory
    {
        return AssetCategory::query()->create([
            'name' => $payload['name'],
            'slug' => $this->buildUniqueSlug($payload['name']),
            'description' => $payload['description'] ?: null,
        ]);
    }

    /**
     * @param array{name:string,description?:string|null} $payload
     */
    public function update(AssetCategory $category, array $payload): AssetCategory
    {
        $category->update([
            'name' => $payload['name'],
            'slug' => $this->buildUniqueSlug($payload['name'], $category->id),
            'description' => $payload['description'] ?: null,
        ]);

        return $category->fresh();
    }

    private function buildUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'activo';
        $slug = $base;
        $counter = 1;

        while (
            AssetCategory::query()
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
