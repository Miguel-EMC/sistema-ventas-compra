<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Purchases\PurchaseOrderManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StorePurchaseOrderPaymentRequest;
use App\Http\Resources\PurchaseOrderPaymentResource;
use App\Models\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderPaymentController extends Controller
{
    public function __invoke(
        StorePurchaseOrderPaymentRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    )
    {
        return PurchaseOrderPaymentResource::make(
            $service->registerPayment($purchaseOrder, $request->validated(), $request->user()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
