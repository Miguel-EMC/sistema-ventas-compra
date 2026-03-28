<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\BusinessInsightsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportOverviewController extends Controller
{
    public function __invoke(Request $request, BusinessInsightsService $service): JsonResponse
    {
        return response()->json([
            'data' => $service->reportsOverview(
                dateFrom: $request->query('date_from'),
                dateTo: $request->query('date_to'),
            ),
        ]);
    }
}
