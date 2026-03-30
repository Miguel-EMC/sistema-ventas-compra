<?php

namespace App\Application\Services\Billing;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Support\Pdf\BillingDocumentPdf;

class BillingPdfService
{
    public function renderInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'taxResolution',
            'items',
        ]);

        $pdf = $this->newDocument("Factura {$invoice->invoice_number}");

        $this->renderDocumentHeader(
            $pdf,
            type: 'Factura',
            number: (string) $invoice->invoice_number,
            companyName: (string) $invoice->company_name_snapshot,
            companyTaxId: $invoice->company_tax_id_snapshot,
            metadata: [
                ['Propietario', $this->metadataValue($invoice->metadata, 'billing_owner_name', 'No registrado')],
                ['Direccion', $this->buildAddressLine($invoice->metadata)],
                ['Telefono', $this->metadataValue($invoice->metadata, 'company_phone', 'No registrado')],
                ['Fecha', $invoice->issued_at?->format('Y-m-d H:i') ?? 'Sin fecha'],
                ['Autorizacion', $invoice->authorization_number_snapshot ?: 'Sin dato'],
                ['Cliente', $invoice->customer_name_snapshot],
                ['Documento', $invoice->customer_document_number_snapshot ?: 'Consumidor final'],
                ['Moneda', $invoice->currency_code ?: 'USD'],
            ],
        );

        $this->renderItemsTable(
            $pdf,
            $invoice->items->map(fn ($item): array => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->all(),
        );

        $this->renderTotals(
            $pdf,
            subtotal: (float) $invoice->subtotal,
            taxTotal: (float) $invoice->tax_total,
            grandTotal: (float) $invoice->grand_total,
            grandLabel: 'Total',
        );

        $this->renderNotes($pdf, [
            $invoice->legend,
            $invoice->footer,
        ]);

        return $pdf->Output('S');
    }

    public function renderCreditNote(CreditNote $creditNote): string
    {
        $creditNote->loadMissing([
            'taxResolution',
            'items',
            'user',
        ]);

        $pdf = $this->newDocument("Nota de credito {$creditNote->credit_note_number}");

        $this->renderDocumentHeader(
            $pdf,
            type: 'Nota de credito',
            number: (string) $creditNote->credit_note_number,
            companyName: (string) $creditNote->company_name_snapshot,
            companyTaxId: $creditNote->company_tax_id_snapshot,
            metadata: [
                ['Propietario', $this->metadataValue($creditNote->metadata, 'billing_owner_name', 'No registrado')],
                ['Direccion', $this->buildAddressLine($creditNote->metadata)],
                ['Telefono', $this->metadataValue($creditNote->metadata, 'company_phone', 'No registrado')],
                ['Fecha', $creditNote->issued_at?->format('Y-m-d H:i') ?? 'Sin fecha'],
                ['Factura base', $creditNote->invoice_number_reference],
                ['Autorizacion', $creditNote->authorization_number_snapshot ?: 'Sin dato'],
                ['Cliente', $creditNote->customer_name_snapshot],
                ['Documento', $creditNote->customer_document_number_snapshot ?: 'Consumidor final'],
                ['Emitida por', $creditNote->user?->display_name ?: $creditNote->user?->name ?: 'Sistema'],
            ],
        );

        $this->renderReasonBlock($pdf, (string) $creditNote->reason);

        $this->renderItemsTable(
            $pdf,
            $creditNote->items->map(fn ($item): array => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->all(),
        );

        $this->renderTotals(
            $pdf,
            subtotal: (float) $creditNote->subtotal,
            taxTotal: (float) $creditNote->tax_total,
            grandTotal: (float) $creditNote->grand_total,
            grandLabel: 'Total acreditado',
        );

        $this->renderNotes($pdf, [
            $creditNote->legend,
            $creditNote->footer,
        ]);

        return $pdf->Output('S');
    }

    public function invoiceFilename(Invoice $invoice): string
    {
        return 'factura-'.$this->safeFilenameSegment((string) $invoice->invoice_number).'.pdf';
    }

    public function creditNoteFilename(CreditNote $creditNote): string
    {
        return 'nota-credito-'.$this->safeFilenameSegment((string) $creditNote->credit_note_number).'.pdf';
    }

    private function newDocument(string $title): BillingDocumentPdf
    {
        $pdf = new BillingDocumentPdf();
        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        return $pdf;
    }

    /**
     * @param array<int, array{0: string, 1: string}> $metadata
     */
    private function renderDocumentHeader(
        BillingDocumentPdf $pdf,
        string $type,
        string $number,
        string $companyName,
        ?string $companyTaxId,
        array $metadata,
    ): void {
        $pdf->SetFillColor(20, 61, 89);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 11, $pdf->safeText($type.' '.$number), 0, 1, 'L', true);

        $pdf->SetTextColor(20, 32, 51);
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 6, $pdf->safeText($companyName), 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(81, 96, 116);
        $pdf->Cell(0, 5, $pdf->safeText('RUC/NIT: '.($companyTaxId ?: 'No registrado')), 0, 1);
        $pdf->Ln(3);

        foreach ($metadata as [$label, $value]) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(20, 32, 51);
            $pdf->Cell(30, 5, $pdf->safeText($label.':'), 0, 0);

            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->Cell(0, 5, $pdf->safeText($value), 0, 1);
        }

        $pdf->Ln(4);
    }

    private function renderReasonBlock(BillingDocumentPdf $pdf, string $reason): void
    {
        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(0, 7, $pdf->safeText('Motivo'), 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(81, 96, 116);
        $pdf->MultiCell(0, 6, $pdf->safeText($reason), 1, 'L');
        $pdf->Ln(4);
    }

    /**
     * @param array<int, array{description: string, quantity: float, unit_price: float, line_total: float}> $items
     */
    private function renderItemsTable(BillingDocumentPdf $pdf, array $items): void
    {
        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(96, 8, $pdf->safeText('Detalle'), 1, 0, 'L', true);
        $pdf->Cell(22, 8, $pdf->safeText('Cantidad'), 1, 0, 'C', true);
        $pdf->Cell(30, 8, $pdf->safeText('Precio'), 1, 0, 'R', true);
        $pdf->Cell(30, 8, $pdf->safeText('Total'), 1, 1, 'R', true);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(20, 32, 51);

        foreach ($items as $item) {
            $pdf->Cell(96, 7, $pdf->safeText($this->truncateText($item['description'], 58)), 1, 0, 'L');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatNumber($item['quantity'])), 1, 0, 'C');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatCurrency($item['unit_price'])), 1, 0, 'R');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatCurrency($item['line_total'])), 1, 1, 'R');
        }

        $pdf->Ln(4);
    }

    private function renderTotals(
        BillingDocumentPdf $pdf,
        float $subtotal,
        float $taxTotal,
        float $grandTotal,
        string $grandLabel,
    ): void {
        $pdf->SetX(110);
        $this->renderTotalRow($pdf, 'Subtotal', $subtotal, false);
        $pdf->SetX(110);
        $this->renderTotalRow($pdf, 'Impuesto', $taxTotal, false);
        $pdf->SetX(110);
        $this->renderTotalRow($pdf, $grandLabel, $grandTotal, true);
        $pdf->Ln(4);
    }

    private function renderTotalRow(BillingDocumentPdf $pdf, string $label, float $amount, bool $highlight): void
    {
        if ($highlight) {
            $pdf->SetFillColor(20, 61, 89);
            $pdf->SetTextColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(243, 244, 246);
            $pdf->SetTextColor(20, 32, 51);
        }

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', $highlight ? 'B' : '', $highlight ? 10 : 9);
        $pdf->Cell(36, 8, $pdf->safeText($label), 1, 0, 'L', true);
        $pdf->Cell(38, 8, $pdf->safeText($this->formatCurrency($amount)), 1, 1, 'R', true);
    }

    /**
     * @param array<int, string|null> $notes
     */
    private function renderNotes(BillingDocumentPdf $pdf, array $notes): void
    {
        foreach ($notes as $note) {
            $normalized = trim((string) ($note ?? ''));

            if ($normalized === '') {
                continue;
            }

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText($normalized), 0, 'L');
            $pdf->Ln(1);
        }
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    private function metadataValue(?array $metadata, string $key, string $fallback = 'Sin dato'): string
    {
        $value = trim((string) ($metadata[$key] ?? ''));

        return $value === '' ? $fallback : $value;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    private function buildAddressLine(?array $metadata): string
    {
        $address = trim((string) ($metadata['company_address_line'] ?? ''));
        $reference = trim((string) ($metadata['billing_address_reference'] ?? ''));
        $city = trim((string) ($metadata['company_city'] ?? ''));
        $region = trim((string) ($metadata['company_region'] ?? ''));

        $segments = array_values(array_filter(
            [$address, $reference, $city, $region],
            static fn (string $value): bool => $value !== ''
        ));

        return $segments === [] ? 'No registrada' : implode(' · ', $segments);
    }

    private function truncateText(string $value, int $limit): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit - 3, '', 'UTF-8')).'...';
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function formatNumber(float $value): string
    {
        $normalized = number_format($value, 2, '.', '');

        return str_contains($normalized, '.00')
            ? (string) (int) round($value)
            : rtrim(rtrim($normalized, '0'), '.');
    }

    private function safeFilenameSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'documento';
        $sanitized = trim($normalized, '-_.');

        return $sanitized === '' ? 'documento' : $sanitized;
    }
}
