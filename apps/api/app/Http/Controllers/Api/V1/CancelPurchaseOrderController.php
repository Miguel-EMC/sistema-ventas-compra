<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Purchases\PurchaseOrderManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\CancelPurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;

class CancelPurchaseOrderController extends Controller
{
    public function __invoke(
        CancelPurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    ): PurchaseOrderResource {
        return PurchaseOrderResource::make(
            $service->cancel($purchaseOrder, $request->validated(), $request->user()),
        );
    }
}
