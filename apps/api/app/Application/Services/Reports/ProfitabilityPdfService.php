<?php

namespace App\Application\Services\Reports;

use App\Models\CompanyProfile;
use App\Support\Pdf\BillingDocumentPdf;

class ProfitabilityPdfService
{
    /**
     * @param array<string, mixed> $report
     */
    public function render(array $report): string
    {
        [$companyName, $companyTaxId] = $this->companyIdentity();
        $pdf = new BillingDocumentPdf();
        $title = 'Utilidad operativa';

        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        $this->renderHeader($pdf, $title, $companyName, $companyTaxId, $report);
        $this->renderSummary($pdf, $report);
        $this->renderExpenseCategories($pdf, $report);
        $this->renderOperationalMovements($pdf, $report);
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
            'utilidad-operativa-%s-a-%s.pdf',
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
        $profitability = $report['profitability'] ?? [];

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
            ['Ventas del rango', $this->formatCurrency((float) ($report['summary']['sales_total'] ?? 0))],
            ['Ventas netas', $this->formatCurrency((float) ($profitability['net_sales_total'] ?? 0))],
            ['Utilidad neta', $this->formatCurrency((float) ($profitability['net_utility_total'] ?? 0))],
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
        $profitability = $report['profitability'] ?? [];

        $rows = [
            ['Ventas brutas', (float) ($summary['sales_total'] ?? 0), 'Ventas netas', (float) ($profitability['net_sales_total'] ?? 0)],
            ['Devoluciones', (float) ($profitability['refund_total'] ?? 0), 'Costo neto', (float) ($profitability['cost_total'] ?? 0)],
            ['Margen bruto', (float) ($profitability['gross_margin_total'] ?? 0), 'Ingresos operativos', (float) ($profitability['operational_income_total'] ?? 0)],
            ['Gastos operativos', (float) ($profitability['operational_expenses_total'] ?? 0), 'Utilidad neta', (float) ($profitability['net_utility_total'] ?? 0)],
        ];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Resumen de utilidad'), 0, 1);

        foreach ($rows as $index => [$leftLabel, $leftAmount, $rightLabel, $rightAmount]) {
            $highlight = $index === array_key_last($rows);

            if ($highlight) {
                $pdf->SetFillColor(20, 61, 89);
                $pdf->SetTextColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(243, 244, 246);
                $pdf->SetTextColor(20, 32, 51);
            }

            $pdf->SetDrawColor(203, 213, 225);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(48, 8, $pdf->safeText($leftLabel), 1, 0, 'L', true);
            $pdf->Cell(26, 8, $pdf->safeText($this->formatCurrency($leftAmount)), 1, 0, 'R', true);
            $pdf->Cell(48, 8, $pdf->safeText($rightLabel), 1, 0, 'L', true);
            $pdf->Cell(26, 8, $pdf->safeText($this->formatCurrency($rightAmount)), 1, 1, 'R', true);
        }

        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderExpenseCategories(BillingDocumentPdf $pdf, array $report): void
    {
        $categories = $report['expense_categories'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Gastos por categoria'), 0, 1);

        if (! is_array($categories) || $categories === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay gastos operativos manuales en el rango seleccionado.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(110, 7, $pdf->safeText('Categoria'), 1, 0, 'L', true);
        $pdf->Cell(24, 7, $pdf->safeText('Movtos'), 1, 0, 'C', true);
        $pdf->Cell(38, 7, $pdf->safeText('Total'), 1, 1, 'R', true);

        $pdf->SetFont('Helvetica', '', 8);
        foreach ($categories as $category) {
            if (! is_array($category)) {
                continue;
            }

            $pdf->Cell(110, 7, $pdf->safeText($this->formatCategory((string) ($category['category'] ?? 'sin_categoria'))), 1, 0, 'L');
            $pdf->Cell(24, 7, $pdf->safeText((string) ($category['count'] ?? 0)), 1, 0, 'C');
            $pdf->Cell(38, 7, $pdf->safeText($this->formatCurrency((float) ($category['total'] ?? 0))), 1, 1, 'R');
        }

        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderOperationalMovements(BillingDocumentPdf $pdf, array $report): void
    {
        $movements = $report['operational_movements'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Movimientos operativos'), 0, 1);

        if (! is_array($movements) || $movements === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay movimientos operativos manuales para este rango.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(18, 7, $pdf->safeText('Tipo'), 1, 0, 'L', true);
        $pdf->Cell(28, 7, $pdf->safeText('Categoria'), 1, 0, 'L', true);
        $pdf->Cell(22, 7, $pdf->safeText('Caja'), 1, 0, 'L', true);
        $pdf->Cell(26, 7, $pdf->safeText('Usuario'), 1, 0, 'L', true);
        $pdf->Cell(24, 7, $pdf->safeText('Fecha'), 1, 0, 'C', true);
        $pdf->Cell(22, 7, $pdf->safeText('Monto'), 1, 0, 'R', true);
        $pdf->Cell(48, 7, $pdf->safeText('Nota'), 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($movements as $movement) {
            if (! is_array($movement)) {
                continue;
            }

            $pdf->Cell(18, 7, $pdf->safeText($this->formatMovementType((string) ($movement['type'] ?? ''))), 1, 0, 'L');
            $pdf->Cell(28, 7, $pdf->safeText($this->truncateText($this->formatCategory((string) ($movement['category'] ?? 'sin_categoria')), 18)), 1, 0, 'L');
            $pdf->Cell(22, 7, $pdf->safeText($this->truncateText((string) ($movement['register_name'] ?? 'Sin caja'), 14)), 1, 0, 'L');
            $pdf->Cell(26, 7, $pdf->safeText($this->truncateText((string) ($movement['user_name'] ?? 'Sin usuario'), 16)), 1, 0, 'L');
            $pdf->Cell(24, 7, $pdf->safeText($this->formatDateTime((string) ($movement['occurred_at'] ?? ''))), 1, 0, 'C');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($movement['amount'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(48, 7, $pdf->safeText($this->truncateText((string) ($movement['notes'] ?? 'Sin observaciones'), 32)), 1, 1, 'L');
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
            $pdf->safeText('Documento generado desde el nuevo modulo de reportes para seguimiento de utilidad y gastos operativos.'),
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

    private function formatDateTime(string $value): string
    {
        if ($value === '') {
            return 'Sin fecha';
        }

        return substr(str_replace('T', ' ', $value), 0, 16);
    }

    private function formatCategory(string $value): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return 'Sin categoria';
        }

        return str_replace('_', ' ', ucwords(str_replace('_', ' ', $normalized)));
    }

    private function formatMovementType(string $value): string
    {
        return match ($value) {
            'income' => 'Ingreso',
            'expense' => 'Gasto',
            default => $value === '' ? 'Movimiento' : ucfirst($value),
        };
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
