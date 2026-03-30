<?php

namespace App\Application\Services\Reports;

use App\Models\CompanyProfile;
use App\Support\Pdf\BillingDocumentPdf;

class ReceivablesPdfService
{
    /**
     * @param array<string, mixed> $report
     */
    public function render(array $report): string
    {
        [$companyName, $companyTaxId] = $this->companyIdentity();
        $pdf = new BillingDocumentPdf();
        $title = $this->statementTitle($report);

        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        $this->renderHeader($pdf, $title, $companyName, $companyTaxId, $report);
        $this->renderSummary($pdf, $report);

        if (($report['receivables']['customer'] ?? null) === null) {
            $this->renderCustomersTable($pdf, $report);
        }

        $this->renderSalesTable($pdf, $report);
        $this->renderFooterNotes($pdf, $report);

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
        $customer = $report['receivables']['customer'] ?? null;

        if (is_array($customer) && isset($customer['name'])) {
            return sprintf(
                'estado-cuenta-%s-%s-a-%s.pdf',
                $this->safeFilenameSegment((string) $customer['name']),
                $this->safeFilenameSegment($dateFrom),
                $this->safeFilenameSegment($dateTo),
            );
        }

        return sprintf(
            'cartera-clientes-%s-a-%s.pdf',
            $this->safeFilenameSegment($dateFrom),
            $this->safeFilenameSegment($dateTo),
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    private function statementTitle(array $report): string
    {
        $customer = $report['receivables']['customer'] ?? null;

        if (is_array($customer) && isset($customer['name'])) {
            return 'Estado de cuenta';
        }

        return 'Cartera por cobrar';
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
        $customer = $report['receivables']['customer'] ?? null;

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
            ['Cliente', is_array($customer) ? ((string) ($customer['name'] ?? 'Sin cliente')) : 'Todos los clientes'],
            ['Saldo pendiente', $this->formatCurrency((float) ($report['receivables']['balance_due_total'] ?? 0))],
            ['Ventas con saldo', (string) ($report['receivables']['sales_count'] ?? 0)],
            ['Clientes con saldo', (string) ($report['receivables']['customers_count'] ?? 0)],
        ];

        foreach ($metadata as [$label, $value]) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(20, 32, 51);
            $pdf->Cell(34, 5, $pdf->safeText($label.':'), 0, 0);

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

        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(60, 8, $pdf->safeText('Ventas del rango'), 1, 0, 'L', true);
        $pdf->Cell(30, 8, $pdf->safeText($this->formatCurrency((float) ($summary['sales_total'] ?? 0))), 1, 0, 'R', true);
        $pdf->Cell(60, 8, $pdf->safeText('Cobros en efectivo'), 1, 0, 'L', true);
        $pdf->Cell(30, 8, $pdf->safeText($this->formatCurrency((float) ($summary['cash_sales_total'] ?? 0))), 1, 1, 'R', true);

        $pdf->Cell(60, 8, $pdf->safeText('Ticket promedio'), 1, 0, 'L', true);
        $pdf->Cell(30, 8, $pdf->safeText($this->formatCurrency((float) ($summary['average_ticket'] ?? 0))), 1, 0, 'R', true);
        $pdf->Cell(60, 8, $pdf->safeText('Cartera pendiente'), 1, 0, 'L', true);
        $pdf->Cell(30, 8, $pdf->safeText($this->formatCurrency((float) ($summary['receivables_total'] ?? 0))), 1, 1, 'R', true);
        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderCustomersTable(BillingDocumentPdf $pdf, array $report): void
    {
        $customers = $report['receivables']['customers'] ?? [];

        if (! is_array($customers) || $customers === []) {
            return;
        }

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Resumen por cliente'), 0, 1);

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(66, 7, $pdf->safeText('Cliente'), 1, 0, 'L', true);
        $pdf->Cell(18, 7, $pdf->safeText('Ventas'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, $pdf->safeText('Neto'), 1, 0, 'R', true);
        $pdf->Cell(30, 7, $pdf->safeText('Cobrado'), 1, 0, 'R', true);
        $pdf->Cell(30, 7, $pdf->safeText('Saldo'), 1, 1, 'R', true);

        $pdf->SetFont('Helvetica', '', 8);
        foreach ($customers as $customer) {
            if (! is_array($customer)) {
                continue;
            }

            $label = (string) ($customer['name'] ?? 'Cliente');
            $document = trim((string) ($customer['document_number'] ?? ''));

            if ($document !== '') {
                $label .= ' / '.$document;
            }

            $pdf->Cell(66, 7, $pdf->safeText($this->truncateText($label, 42)), 1, 0, 'L');
            $pdf->Cell(18, 7, $pdf->safeText((string) ($customer['sales_count'] ?? 0)), 1, 0, 'C');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatCurrency((float) ($customer['net_receivable_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatCurrency((float) ($customer['paid_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatCurrency((float) ($customer['balance_due'] ?? 0))), 1, 1, 'R');
        }

        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderSalesTable(BillingDocumentPdf $pdf, array $report): void
    {
        $sales = $report['receivables']['sales'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Ventas con saldo pendiente'), 0, 1);

        if (! is_array($sales) || $sales === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay ventas con saldo pendiente para el filtro seleccionado.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(62, 7, $pdf->safeText('Venta / Cliente'), 1, 0, 'L', true);
        $pdf->Cell(26, 7, $pdf->safeText('Fecha'), 1, 0, 'C', true);
        $pdf->Cell(24, 7, $pdf->safeText('Documento'), 1, 0, 'L', true);
        $pdf->Cell(22, 7, $pdf->safeText('Total'), 1, 0, 'R', true);
        $pdf->Cell(22, 7, $pdf->safeText('Cobrado'), 1, 0, 'R', true);
        $pdf->Cell(22, 7, $pdf->safeText('Saldo'), 1, 1, 'R', true);

        $pdf->SetFont('Helvetica', '', 8);
        foreach ($sales as $sale) {
            if (! is_array($sale)) {
                continue;
            }

            $saleLabel = trim((string) (($sale['public_id'] ?? ('#'.($sale['id'] ?? '0'))).' / '.($sale['customer_name'] ?? 'Consumidor final')));
            $document = (string) ($sale['invoice_number'] ?? $sale['document_type'] ?? 'ticket');

            $pdf->Cell(62, 7, $pdf->safeText($this->truncateText($saleLabel, 40)), 1, 0, 'L');
            $pdf->Cell(26, 7, $pdf->safeText($this->formatDateTime((string) ($sale['sold_at'] ?? ''))), 1, 0, 'C');
            $pdf->Cell(24, 7, $pdf->safeText($this->truncateText($document, 18)), 1, 0, 'L');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($sale['grand_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($sale['paid_total'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($sale['balance_due'] ?? 0))), 1, 1, 'R');
        }

        $pdf->Ln(4);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderFooterNotes(BillingDocumentPdf $pdf, array $report): void
    {
        $customer = $report['receivables']['customer'] ?? null;
        $note = is_array($customer)
            ? 'Documento generado desde el nuevo modulo de reportes y cobranza para seguimiento comercial.'
            : 'Documento generado desde el nuevo modulo de reportes para seguimiento de cartera comercial.';

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(81, 96, 116);
        $pdf->MultiCell(0, 5, $pdf->safeText($note), 0, 'L');
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

    private function formatDateTime(string $value): string
    {
        if ($value === '') {
            return 'Sin fecha';
        }

        return substr(str_replace('T', ' ', $value), 0, 16);
    }

    private function safeFilenameSegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'documento';
        $sanitized = trim($normalized, '-_.');

        return $sanitized === '' ? 'documento' : $sanitized;
    }
}
