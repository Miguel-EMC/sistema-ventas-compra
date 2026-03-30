<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CancelSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;

class CancelSaleController extends Controller
{
    public function __invoke(
        CancelSaleRequest $request,
        Sale $sale,
        SaleWorkflowService $service,
    ): SaleResource {
        return SaleResource::make(
            $service->cancelSale($request->user(), $sale, $request->validated()),
        );
    }
}
