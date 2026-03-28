<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Catalog\ProductManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->with('category')
            ->withSum('stockMovements as current_stock', 'quantity')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request, ProductManagementService $service)
    {
        $product = $service->create($request->validated(), $request->user());

        return ProductResource::make(
            $product->load('category')->loadSum('stockMovements as current_stock', 'quantity'),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): ProductResource
    {
        return ProductResource::make(
            $product->load('category')->loadSum('stockMovements as current_stock', 'quantity'),
        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductManagementService $service,
    ): ProductResource {
        return ProductResource::make(
            $service->update($product, $request->validated())
                ->load('category')
                ->loadSum('stockMovements as current_stock', 'quantity'),
        );
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
