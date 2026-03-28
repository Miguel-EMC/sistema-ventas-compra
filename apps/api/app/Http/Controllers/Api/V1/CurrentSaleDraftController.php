<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\UpdateSaleDraftRequest;
use App\Http\Resources\SaleDraftResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrentSaleDraftController extends Controller
{
    public function show(Request $request, SaleWorkflowService $service)
    {
        return SaleDraftResource::make(
            $service->getCurrentDraft($request->user()),
        )->response()->setStatusCode(Response::HTTP_OK);
    }

    public function update(
        UpdateSaleDraftRequest $request,
        SaleWorkflowService $service,
    ): SaleDraftResource {
        return SaleDraftResource::make(
            $service->updateDraft($request->user(), $request->validated()),
        );
    }
}
