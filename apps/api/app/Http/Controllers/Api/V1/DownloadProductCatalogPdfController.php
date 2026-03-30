<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Reports\CatalogExportService;
use App\Application\Services\Reports\CatalogPdfService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadProductCatalogPdfController extends Controller
{
    public function __invoke(
        Request $request,
        CatalogExportService $catalogExportService,
        CatalogPdfService $pdfService,
    ): Response {
        $report = $catalogExportService->productCatalog($request->query('search'));

        return response(
            $pdfService->renderProductCatalog($report),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $pdfService->productFilename($report),
                ),
            ],
        );
    }
}
