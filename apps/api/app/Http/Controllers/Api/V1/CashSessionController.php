<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Cash\CashSessionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\OpenCashSessionRequest;
use App\Http\Resources\CashSessionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CashSessionController extends Controller
{
    public function index(CashSessionService $service): AnonymousResourceCollection
    {
        return CashSessionResource::collection($service->recentSessions());
    }

    public function store(OpenCashSessionRequest $request, CashSessionService $service)
    {
        return CashSessionResource::make(
            $service->open($request->user(), $request->validated()),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function current(Request $request, CashSessionService $service)
    {
        $session = $service->getCurrentSession($request->user());

        return response()->json([
            'data' => $session ? CashSessionResource::make($session)->resolve() : null,
        ]);
    }
}
