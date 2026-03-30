<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Cash\CashMovementManagementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\StoreCashMovementRequest;
use App\Http\Requests\Cash\UpdateCashMovementRequest;
use App\Http\Resources\CashMovementResource;
use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CashMovementController extends Controller
{
    public function index(Request $request, CashMovementManagementService $service): AnonymousResourceCollection
    {
        return CashMovementResource::collection($service->list($request->user(), [
            'type' => $request->query('type'),
            'search' => $request->query('search'),
        ]));
    }

    public function store(StoreCashMovementRequest $request, CashMovementManagementService $service)
    {
        return CashMovementResource::make(
            $service->create($request->user(), $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateCashMovementRequest $request,
        CashMovement $cashMovement,
        CashMovementManagementService $service,
    ): CashMovementResource {
        return CashMovementResource::make(
            $service->update($cashMovement, $request->user(), $request->validated()),
        );
    }

    public function destroy(
        CashMovement $cashMovement,
        CashMovementManagementService $service,
    )
    {
        $service->delete($cashMovement, request()->user());

        return response()->json([
            'message' => 'Movimiento de caja eliminado correctamente.',
        ]);
    }
}
