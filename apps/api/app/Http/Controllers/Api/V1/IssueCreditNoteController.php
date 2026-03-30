<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Billing\CreditNoteIssuanceService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreditNoteResource;
use App\Models\SaleReturn;
use Symfony\Component\HttpFoundation\Response;

class IssueCreditNoteController extends Controller
{
    public function __invoke(
        SaleReturn $saleReturn,
        CreditNoteIssuanceService $service,
    ) {
        return CreditNoteResource::make(
            $service->issueForSaleReturn(request()->user(), $saleReturn),
        )->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
