<?php

namespace App\Application\Services\Reports;

use App\Models\CompanyProfile;
use App\Support\Pdf\BillingDocumentPdf;

class CatalogPdfService
{
    /**
     * @param array<string, mixed> $report
     */
    public function renderProductCatalog(array $report): string
    {
        [$companyName, $companyTaxId] = $this->companyIdentity();
        $pdf = new BillingDocumentPdf();
        $title = 'Catalogo de productos';

        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        $this->renderHeader($pdf, $title, $companyName, $companyTaxId, $report);
        $this->renderProductSummary($pdf, $report);
        $this->renderProductTable($pdf, $report);
        $this->renderFooterNotes($pdf, 'Documento generado desde el catalogo comercial del stack nuevo.');

        return $pdf->Output('S');
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderAssetCatalog(array $report): string
    {
        [$companyName, $companyTaxId] = $this->companyIdentity();
        $pdf = new BillingDocumentPdf();
        $title = 'Catalogo de activos';

        $pdf->SetTitle($pdf->safeText($title));
        $pdf->AddPage();

        $this->renderHeader($pdf, $title, $companyName, $companyTaxId, $report);
        $this->renderAssetSummary($pdf, $report);
        $this->renderAssetTable($pdf, $report);
        $this->renderFooterNotes($pdf, 'Documento generado desde el modulo nuevo de activos internos.');

        return $pdf->Output('S');
    }

    /**
     * @param array<string, mixed> $report
     */
    public function productFilename(array $report): string
    {
        return 'catalogo-productos'.$this->searchSuffix($report).'.pdf';
    }

    /**
     * @param array<string, mixed> $report
     */
    public function assetFilename(array $report): string
    {
        return 'catalogo-activos'.$this->searchSuffix($report).'.pdf';
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
        $pdf->Cell(0, 5, $pdf->safeText('Emitido: '.$this->formatDateTime((string) ($report['generated_at'] ?? ''))), 0, 1);

        $search = trim((string) ($report['search'] ?? ''));
        if ($search !== '') {
            $pdf->Cell(0, 5, $pdf->safeText('Filtro: '.$search), 0, 1);
        }

        $pdf->Ln(4);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderProductSummary(BillingDocumentPdf $pdf, array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];

        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(52, 8, $pdf->safeText('Productos'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['products_count'] ?? 0)), 1, 0, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Con stock trazado'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['tracked_count'] ?? 0)), 1, 1, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Activos'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['active_count'] ?? 0)), 1, 0, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Stock bajo'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['low_stock_count'] ?? 0)), 1, 1, 'R', true);
        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderAssetSummary(BillingDocumentPdf $pdf, array $report): void
    {
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];

        $pdf->SetFillColor(243, 244, 246);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(52, 8, $pdf->safeText('Activos'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['assets_count'] ?? 0)), 1, 0, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('En servicio'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText((string) ($summary['active_count'] ?? 0)), 1, 1, 'R', true);
        $pdf->Cell(52, 8, $pdf->safeText('Unidades totales'), 1, 0, 'L', true);
        $pdf->Cell(34, 8, $pdf->safeText($this->formatNumber((float) ($summary['total_units'] ?? 0))), 1, 0, 'R', true);
        $pdf->Cell(86, 8, $pdf->safeText('Base nueva de activos internos'), 1, 1, 'C', true);
        $pdf->Ln(5);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderProductTable(BillingDocumentPdf $pdf, array $report): void
    {
        $items = $report['items'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Detalle del catalogo'), 0, 1);

        if (! is_array($items) || $items === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay productos para el filtro seleccionado.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(34, 7, $pdf->safeText('Producto'), 1, 0, 'L', true);
        $pdf->Cell(20, 7, $pdf->safeText('SKU'), 1, 0, 'L', true);
        $pdf->Cell(24, 7, $pdf->safeText('Categoria'), 1, 0, 'L', true);
        $pdf->Cell(12, 7, $pdf->safeText('Unidad'), 1, 0, 'C', true);
        $pdf->Cell(18, 7, $pdf->safeText('Venta'), 1, 0, 'R', true);
        $pdf->Cell(18, 7, $pdf->safeText('Costo'), 1, 0, 'R', true);
        $pdf->Cell(18, 7, $pdf->safeText('Stock'), 1, 0, 'R', true);
        $pdf->Cell(19, 7, $pdf->safeText('Minimo'), 1, 0, 'R', true);
        $pdf->Cell(19, 7, $pdf->safeText('Estado'), 1, 1, 'C', true);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pdf->Cell(34, 7, $pdf->safeText($this->truncateText((string) ($item['name'] ?? 'Sin producto'), 22)), 1, 0, 'L');
            $pdf->Cell(20, 7, $pdf->safeText($this->truncateText((string) ($item['sku'] ?? ''), 12)), 1, 0, 'L');
            $pdf->Cell(24, 7, $pdf->safeText($this->truncateText((string) ($item['category_name'] ?? 'Sin categoria'), 14)), 1, 0, 'L');
            $pdf->Cell(12, 7, $pdf->safeText($this->truncateText((string) ($item['unit'] ?? 'u'), 8)), 1, 0, 'C');
            $pdf->Cell(18, 7, $pdf->safeText($this->formatCurrency((float) ($item['sale_price'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(18, 7, $pdf->safeText($this->formatCurrency((float) ($item['cost_price'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(18, 7, $pdf->safeText($this->formatNumber((float) ($item['current_stock'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(19, 7, $pdf->safeText($this->formatNumber((float) ($item['minimum_stock'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(19, 7, $pdf->safeText($this->productStatusLabel($item)), 1, 1, 'C');
        }

        $pdf->Ln(4);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderAssetTable(BillingDocumentPdf $pdf, array $report): void
    {
        $items = $report['items'] ?? [];

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(20, 32, 51);
        $pdf->Cell(0, 6, $pdf->safeText('Detalle de activos'), 0, 1);

        if (! is_array($items) || $items === []) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(81, 96, 116);
            $pdf->MultiCell(0, 5, $pdf->safeText('No hay activos para el filtro seleccionado.'), 1, 'L');
            $pdf->Ln(3);

            return;
        }

        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(42, 7, $pdf->safeText('Activo'), 1, 0, 'L', true);
        $pdf->Cell(22, 7, $pdf->safeText('Codigo'), 1, 0, 'L', true);
        $pdf->Cell(26, 7, $pdf->safeText('Categoria'), 1, 0, 'L', true);
        $pdf->Cell(16, 7, $pdf->safeText('Cant.'), 1, 0, 'R', true);
        $pdf->Cell(22, 7, $pdf->safeText('Costo'), 1, 0, 'R', true);
        $pdf->Cell(24, 7, $pdf->safeText('Estado'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, $pdf->safeText('Adquirido'), 1, 1, 'C', true);

        $pdf->SetFont('Helvetica', '', 7);
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pdf->Cell(42, 7, $pdf->safeText($this->truncateText((string) ($item['name'] ?? 'Sin activo'), 28)), 1, 0, 'L');
            $pdf->Cell(22, 7, $pdf->safeText($this->truncateText((string) ($item['code'] ?? ''), 14)), 1, 0, 'L');
            $pdf->Cell(26, 7, $pdf->safeText($this->truncateText((string) ($item['category_name'] ?? 'Sin categoria'), 16)), 1, 0, 'L');
            $pdf->Cell(16, 7, $pdf->safeText($this->formatNumber((float) ($item['quantity'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(22, 7, $pdf->safeText($this->formatCurrency((float) ($item['acquisition_cost'] ?? 0))), 1, 0, 'R');
            $pdf->Cell(24, 7, $pdf->safeText($this->assetStatusLabel((string) ($item['status'] ?? ''))), 1, 0, 'C');
            $pdf->Cell(30, 7, $pdf->safeText($this->formatDate((string) ($item['acquired_at'] ?? ''))), 1, 1, 'C');
        }

        $pdf->Ln(4);
    }

    private function renderFooterNotes(BillingDocumentPdf $pdf, string $note): void
    {
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

    /**
     * @param array<string, mixed> $report
     */
    private function searchSuffix(array $report): string
    {
        $search = trim((string) ($report['search'] ?? ''));

        return $search === '' ? '' : '-'.$this->safeFilenameSegment($search);
    }

    /**
     * @param mixed $item
     */
    private function productStatusLabel(mixed $item): string
    {
        if (! is_array($item)) {
            return 'Sin estado';
        }

        if (! (bool) ($item['is_active'] ?? false)) {
            return 'Inactivo';
        }

        if ((bool) ($item['is_low_stock'] ?? false)) {
            return 'Stock bajo';
        }

        return 'Activo';
    }

    private function assetStatusLabel(string $status): string
    {
        return match ($status) {
            'maintenance' => 'Mantenimiento',
            'inactive' => 'Inactivo',
            'retired' => 'Retirado',
            default => 'Activo',
        };
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
            return now()->format('Y-m-d H:i');
        }

        return substr(str_replace('T', ' ', $value), 0, 16);
    }

    private function formatDate(string $value): string
    {
        return $value === '' ? 'Sin fecha' : $value;
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
