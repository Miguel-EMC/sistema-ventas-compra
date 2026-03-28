<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\BusinessInsightsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardSummaryController extends Controller
{
    public function __invoke(BusinessInsightsService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->dashboardSummary(),
        ]);
    }
}
