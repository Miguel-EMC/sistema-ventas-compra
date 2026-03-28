<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Purchases\PurchaseOrderManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\ReceivePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;

class ReceivePurchaseOrderController extends Controller
{
    public function __invoke(
        ReceivePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    ): PurchaseOrderResource {
        return PurchaseOrderResource::make(
            $service->receive($purchaseOrder, $request->validated(), $request->user()),
        );
    }
}
