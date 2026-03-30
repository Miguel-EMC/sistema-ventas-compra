<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\CatalogExportService;
use App\Application\Services\Reports\ReportCsvService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadAssetCatalogCsvController extends Controller
{
    public function __invoke(
        Request $request,
        CatalogExportService $catalogExportService,
        ReportCsvService $csvService,
    ): Response {
        $report = $catalogExportService->assetCatalog($request->query('search'));

        return response(
            $csvService->renderAssetCatalog($report),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $csvService->assetCatalogFilename($report),
                ),
            ],
        );
    }
}
