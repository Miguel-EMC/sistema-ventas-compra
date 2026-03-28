<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Sales\SaleWorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleDraftItemRequest;
use App\Http\Requests\Sales\UpdateSaleDraftItemRequest;
use App\Http\Resources\SaleDraftResource;
use App\Models\SaleDraftItem;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaleDraftItemController extends Controller
{
    public function store(
        StoreSaleDraftItemRequest $request,
        SaleWorkflowService $service,
    ) {
        return SaleDraftResource::make(
            $service->addItem($request->user(), $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateSaleDraftItemRequest $request,
        SaleDraftItem $saleDraftItem,
        SaleWorkflowService $service,
    ): SaleDraftResource {
        $this->guardItemOwnership($request, $saleDraftItem);

        return SaleDraftResource::make(
            $service->updateItem($saleDraftItem, $request->validated()),
        );
    }

    public function destroy(
        Request $request,
        SaleDraftItem $saleDraftItem,
        SaleWorkflowService $service,
    ): SaleDraftResource {
        $this->guardItemOwnership($request, $saleDraftItem);

        return SaleDraftResource::make(
            $service->removeItem($saleDraftItem),
        );
    }

    private function guardItemOwnership(Request $request, SaleDraftItem $saleDraftItem): void
    {
        $saleDraftItem->load('draft');

        abort_unless(
            $saleDraftItem->draft !== null
                && $saleDraftItem->draft->user_id === $request->user()?->id
                && $saleDraftItem->draft->status === 'draft',
            Response::HTTP_NOT_FOUND,
        );
    }
}
