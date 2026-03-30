<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalePaymentRequest;
use App\Http\Resources\SalePaymentResource;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\Response;

class SalePaymentController extends Controller
{
    public function __invoke(
        StoreSalePaymentRequest $request,
        Sale $sale,
        SaleWorkflowService $service,
    )
    {
        return SalePaymentResource::make(
            $service->registerPayment($request->user(), $sale, $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
