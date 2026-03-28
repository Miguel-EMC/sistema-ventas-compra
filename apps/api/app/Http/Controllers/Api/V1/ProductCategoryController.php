<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Catalog\ProductCategoryManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductCategoryRequest;
use App\Http\Requests\Catalog\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductCategoryResource::collection(
            ProductCategory::query()->orderBy('name')->get(),
        );
    }

    public function store(
        StoreProductCategoryRequest $request,
        ProductCategoryManagementService $service,
    ) {
        return ProductCategoryResource::make(
            $service->create($request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(ProductCategory $productCategory): ProductCategoryResource
    {
        return ProductCategoryResource::make($productCategory);
    }

    public function update(
        UpdateProductCategoryRequest $request,
        ProductCategory $productCategory,
        ProductCategoryManagementService $service,
    ): ProductCategoryResource {
        return ProductCategoryResource::make(
            $service->update($productCategory, $request->validated()),
        );
    }

    public function destroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return response()->json([
            'message' => 'Categoria de producto eliminada correctamente.',
        ]);
    }
}
