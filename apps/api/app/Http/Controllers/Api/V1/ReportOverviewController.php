<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\BusinessInsightsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportOverviewRequest;
use Illuminate\Http\JsonResponse;

class ReportOverviewController extends Controller
{
    public function __invoke(ReportOverviewRequest $request, BusinessInsightsService $service): JsonResponse
    {
        $filters = $request->validated();

        return response()->json([
            'data' => $service->reportsOverview(
                dateFrom: is_string($filters['date_from'] ?? null) ? $filters['date_from'] : null,
                dateTo: is_string($filters['date_to'] ?? null) ? $filters['date_to'] : null,
                customerId: isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
            ),
        ]);
    }
}
