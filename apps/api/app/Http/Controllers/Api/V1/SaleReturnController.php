<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleReturnRequest;
use App\Http\Resources\SaleReturnResource;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\Response;

class SaleReturnController extends Controller
{
    public function __invoke(
        StoreSaleReturnRequest $request,
        Sale $sale,
        SaleWorkflowService $service,
    ) {
        return SaleReturnResource::make(
            $service->registerReturn($request->user(), $sale, $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
