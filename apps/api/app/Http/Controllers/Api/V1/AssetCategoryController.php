<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Assets\AssetCategoryManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\StoreAssetCategoryRequest;
use App\Http\Requests\Assets\UpdateAssetCategoryRequest;
use App\Http\Resources\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AssetCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AssetCategoryResource::collection(
            AssetCategory::query()->orderBy('name')->get(),
        );
    }

    public function store(
        StoreAssetCategoryRequest $request,
        AssetCategoryManagementService $service,
    ) {
        return AssetCategoryResource::make(
            $service->create($request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AssetCategory $assetCategory): AssetCategoryResource
    {
        return AssetCategoryResource::make($assetCategory);
    }

    public function update(
        UpdateAssetCategoryRequest $request,
        AssetCategory $assetCategory,
        AssetCategoryManagementService $service,
    ): AssetCategoryResource {
        return AssetCategoryResource::make(
            $service->update($assetCategory, $request->validated()),
        );
    }

    public function destroy(AssetCategory $assetCategory)
    {
        $assetCategory->delete();

        return response()->json([
            'message' => 'Categoria de activo eliminada correctamente.',
        ]);
    }
}
