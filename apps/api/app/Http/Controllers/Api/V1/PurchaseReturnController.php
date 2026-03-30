<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Purchases\PurchaseOrderManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StorePurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;

class PurchaseReturnController extends Controller
{
    public function __invoke(
        StorePurchaseReturnRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    ) {
        return PurchaseReturnResource::make(
            $service->registerReturn($purchaseOrder, $request->validated(), $request->user()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
