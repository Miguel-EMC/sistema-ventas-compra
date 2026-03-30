<?php

namespace App\Application\Services\Reports;

use App\Models\CompanyProfile;
use App\Support\Pdf\BillingDocumentPdf;

class SalesReportPdfService
{
    /**
     * @param array<string, mixed> $report
     */
    public function render(array $report): string
    {
        [$companyName, $companyTaxId] = $this->companyIdentity();
        $pdf = new BillingDocumentPdf();
        $title = 'Ventas del rango';

        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        $this->renderHeader($pdf, $title, $companyName, $companyTaxId, $report);
        $this->renderSummary($pdf, $report);
        $this->renderSalesTable($pdf, $report);
        $this->renderFooterNotes($pdf);

        return $pdf->Output('S');
    }

    /**
     * @param array<string, mixed> $report
     */
    public function filename(array $report): string
    {
        $range = $report['range'] ?? [];
        $dateFrom = (string) ($range['date_from'] ?? 'sin-rango');
        $dateTo = (string) ($range['date_to'] ?? 'sin-rango');

        return sprintf(
            'ventas-rango-%s-a-%s.pdf',
            $this->safeFilenameSegment($dateFrom),
            $this->safeFilenameSegment($dateTo),
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderHeader(
        BillingDocumentPdf $pdf,
        string $title,
        string $companyName,
        ?string $companyTaxId,
        array $report,
    ): void {
        $pdf->SetFillColor(20, 61, 89);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 11, $pdf->safeText($title), 0, 1, 'L', true);

        $pdf->Ln(4);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 6, $pdf->safeText($companyName), 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(81, 96, 116);
        $pdf->Cell(0, 5, $pdf->safeText('RUC/NIT: '.($companyTaxId ?: 'No registrado')), 0, 1);
        $pdf->Ln(2);

        $metadata = [
            ['Emitido', now()->format('Y-m-d H:i')],
            ['Rango', sprintf('%s a %s', (string) ($report['range']['date_from'] ?? 'Sin fecha'), (string) ($report['range']['date_to'] ?? 'Sin fecha'))],
            ['Ventas', (string) ($report['summary']['sales_count'] ?? 0)],
            ['Total vendido', $this->formatCurrency((float) ($report['summary']['sales_total'] ?? 0))],
            ['Ticket promedio', $this->formatCurrency((float) ($report['summary']['average_ticket'] ?? 0))],
        ];

        foreach ($metadata as [$label, $value]) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(20, 32, 51);
            $pdf->Cell(32, 5, $pdf->safeText($label.':'), 0, 0);

            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->Cell(0, 5, $pdf->safeText($value), 0, 1);
        }

        $pdf->Ln(4);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderSummary(BillingDocumentPdf $pdf, array $report): void
    {
        $summary = $report['summary'] ?? [];
        $profitability = $report['profitability'] ?? [];

        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(52, 8, $pdf->safeText('Ventas del rango'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText($this->formatCurrency((float) ($summary['sales_total'] ?? 0))), 1, 0, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Ventas netas'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText($this->formatCurrency((float) ($profitability['net_sales_total'] ?? 0))), 1, 1, 'R', true);

        $pdf->Cell(52, 8, $pdf->safeText('Ticket promedio'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText($this->formatCurrency((float) ($summary['average_ticket'] ?? 0))), 1, 0, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Devoluciones'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText($this->formatCurrency((float) ($profitability['refund_total'] ?? 0))), 1, 1, 'R', true);
        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderSalesTable(BillingDocumentPdf $pdf, array $report): void
    {
        $sales = $report['sales_documents'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Detalle de ventas'), 0, 1);

        if (! is_array($sales) || $sales === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay ventas para el rango seleccionado.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(24, 7, $pdf->safeText('Documento'), 1, 0, 'L', true);
        $pdf->Cell(42, 7, $pdf->safeText('Cliente'), 1, 0, 'L', true);
        $pdf->Cell(16, 7, $pdf->safeText('Items'), 1, 0, 'C', true);
        $pdf->Cell(18, 7, $pdf->safeText('Cantidad'), 1, 0, 'C', true);
        $pdf->Cell(22, 7, $pdf->safeText('Total'), 1, 0, 'R', true);
        $pdf->Cell(22, 7, $pdf->safeText('Cobrado'), 1, 0, 'R', true);
        $pdf->Cell(20, 7, $pdf->safeText('Saldo'), 1, 0, 'R', true);
        $pdf->Cell(26, 7, $pdf->safeText('Fecha'), 1, 1, 'C', true);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($sales as $sale) {
            if (! is_array($sale)) {
                continue;
            }

            $pdf->Cell(24, 7, $pdf->safeText($this->truncateText((string) ($sale['document_reference'] ?? 'Sin doc'), 16)), 1, 0, 'L');
            $pdf->Cell(42, 7, $pdf->safeText($this->truncateText((string) ($sale['customer_name'] ?? 'Consumidor final'), 26)), 1, 0, 'L');
            $pdf->Cell(16, 7, $pdf->safeText((string) ($sale['items_count'] ?? 0)), 1, 0, 'C');
            $pdf->Cell(18, 7, $pdf->safeText($this->formatNumber((float) ($sale['quantity_total'] ?? 0))), 1, 0, 'C');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($sale['grand_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($sale['paid_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(20, 7, $pdf->safeText($this->formatCurrency((float) ($sale['balance_due'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(26, 7, $pdf->safeText($this->formatDateTime((string) ($sale['sold_at'] ?? ''))), 1, 1, 'C');
        }

        $pdf->Ln(4);
    }

    private function renderFooterNotes(BillingDocumentPdf $pdf): void
    {
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(81, 96, 116);
        $pdf->MultiCell(
            0,
            5,
            $pdf->safeText('Documento generado desde el nuevo modulo de reportes para seguimiento comercial y cierre de ventas.'),
            0,
            'L',
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function companyIdentity(): array
    {
        $company = CompanyProfile::query()->where('is_primary', true)->first()
            ?? CompanyProfile::query()->first();

        if ($company instanceof CompanyProfile) {
            return [
                $company->trade_name ?: $company->legal_name,
                $company->tax_id,
            ];
        }

        return ['VentasPOS', null];
    }

    private function formatCurrency(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function formatDateTime(string $value): string
    {
        if ($value === '') {
            return 'Sin fecha';
        }

        return substr(str_replace('T', ' ', $value), 0, 16);
    }

    private function truncateText(string $value, int $limit): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit - 3, '', 'UTF-8')).'...';
    }

    private function safeFilenameSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'documento';
        $sanitized = trim($normalized, '-_.');

        return $sanitized === '' ? 'documento' : $sanitized;
    }
}
