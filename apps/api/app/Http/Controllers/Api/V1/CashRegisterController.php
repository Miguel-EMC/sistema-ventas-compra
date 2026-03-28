<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Cash\CashSessionService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CashRegisterResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashRegisterController extends Controller
{
    public function index(CashSessionService $service): AnonymousResourceCollection
    {
        return CashRegisterResource::collection($service->availableRegisters());
    }
}
