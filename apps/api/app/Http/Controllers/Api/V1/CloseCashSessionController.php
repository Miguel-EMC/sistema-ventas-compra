<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Cash\CashSessionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\CloseCashSessionRequest;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;

class CloseCashSessionController extends Controller
{
    public function __invoke(
        CloseCashSessionRequest $request,
        CashSession $cashSession,
        CashSessionService $service,
    ): CashSessionResource {
        return CashSessionResource::make(
            $service->close($request->user(), $cashSession->load('openedBy'), $request->validated()),
        );
    }
}
