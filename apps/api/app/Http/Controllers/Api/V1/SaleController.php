<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CheckoutSaleRequest;
use App\Http\Resources\SaleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    public function index(SaleWorkflowService $service): AnonymousResourceCollection
    {
        return SaleResource::collection($service->recentSales());
    }

    public function store(
        CheckoutSaleRequest $request,
        SaleWorkflowService $service,
    ) {
        return SaleResource::make(
            $service->checkout($request->user(), $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
