<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Purchases\PurchaseOrderManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StorePurchaseOrderRequest;
use App\Http\Requests\Purchases\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, PurchaseOrderManagementService $service): AnonymousResourceCollection
    {
        return PurchaseOrderResource::collection($service->list([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ]));
    }

    public function store(
        StorePurchaseOrderRequest $request,
        PurchaseOrderManagementService $service,
    ) {
        return PurchaseOrderResource::make(
            $service->create($request->validated(), $request->user()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    ): PurchaseOrderResource {
        return PurchaseOrderResource::make($service->loadRelations($purchaseOrder));
    }

    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    ): PurchaseOrderResource {
        return PurchaseOrderResource::make(
            $service->update($purchaseOrder, $request->validated()),
        );
    }

    public function destroy(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderManagementService $service,
    )
    {
        $service->delete($purchaseOrder);

        return response()->json([
            'message' => 'Orden de compra eliminada correctamente.',
        ]);
    }
}
