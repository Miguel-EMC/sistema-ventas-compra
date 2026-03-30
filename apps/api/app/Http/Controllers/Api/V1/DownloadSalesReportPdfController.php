<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\BusinessInsightsService;
use App\Application\Services\Reports\SalesReportPdfService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportOverviewRequest;
use Symfony\Component\HttpFoundation\Response;

class DownloadSalesReportPdfController extends Controller
{
    public function __invoke(
        ReportOverviewRequest $request,
        BusinessInsightsService $insightsService,
        SalesReportPdfService $pdfService,
    ): Response {
        $filters = $request->validated();
        $report = $insightsService->reportsOverview(
            dateFrom: is_string($filters['date_from'] ?? null) ? $filters['date_from'] : null,
            dateTo: is_string($filters['date_to'] ?? null) ? $filters['date_to'] : null,
            customerId: isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
        );

        return response(
            $pdfService->render($report),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $pdfService->filename($report)),
            ],
        );
    }
}
