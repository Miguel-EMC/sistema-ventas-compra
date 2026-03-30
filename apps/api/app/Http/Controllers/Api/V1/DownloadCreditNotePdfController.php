<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Services\Billing\BillingPdfService;
use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Symfony\Component\HttpFoundation\Response;

class DownloadCreditNotePdfController extends Controller
{
    public function __invoke(CreditNote $creditNote, BillingPdfService $service): Response
    {
        return response(
            $service->renderCreditNote($creditNote),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $service->creditNoteFilename($creditNote)),
            ],
        );
    }
}
