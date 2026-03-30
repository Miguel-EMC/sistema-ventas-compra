<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Billing\BillingPdfService;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

class DownloadInvoicePdfController extends Controller
{
    public function __invoke(Invoice $invoice, BillingPdfService $service): Response
    {
        return response(
            $service->renderInvoice($invoice),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $service->invoiceFilename($invoice)),
            ],
        );
    }
}
