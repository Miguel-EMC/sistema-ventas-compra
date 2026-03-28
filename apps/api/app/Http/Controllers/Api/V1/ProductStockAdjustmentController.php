<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Catalog\ProductManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StockAdjustmentRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductStockAdjustmentController extends Controller
{
    public function __invoke(
        StockAdjustmentRequest $request,
        Product $product,
        ProductManagementService $service,
    ): ProductResource {
        return ProductResource::make(
            $service->adjustStock($product, $request->validated(), $request->user())
                ->load('category')
                ->loadSum('stockMovements as current_stock', 'quantity'),
        );
    }
}
