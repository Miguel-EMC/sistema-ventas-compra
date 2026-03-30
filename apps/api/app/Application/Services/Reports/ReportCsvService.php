<?php

namespace App\Application\Services\Reports;

class ReportCsvService
{
    /**
     * @param array<string, mixed> $report
     */
    public function renderSalesReport(array $report): string
    {
        $rows = [
            [
                'Documento',
                'Cliente',
                'Tipo',
                'Items',
                'Cantidad',
                'Cobros',
                'Total',
                'Cobrado',
                'Devuelto',
                'Neto',
                'Saldo',
                'Estado',
                'Fecha',
            ],
        ];

        foreach ($report['sales_documents'] ?? [] as $sale) {
            if (! is_array($sale)) {
                continue;
            }

            $rows[] = [
                (string) ($sale['document_reference'] ?? 'Sin documento'),
                (string) ($sale['customer_name'] ?? 'Consumidor final'),
                $this->documentLabel(
                    isset($sale['invoice_number']) && is_string($sale['invoice_number']) && $sale['invoice_number'] !== ''
                        ? 'factura'
                        : (is_string($sale['document_type'] ?? null) ? $sale['document_type'] : null),
                ),
                (string) ($sale['items_count'] ?? 0),
                $this->formatNumber((float) ($sale['quantity_total'] ?? 0)),
                $this->paymentMethodsLabel($sale['payment_methods'] ?? []),
                $this->formatCurrency((float) ($sale['grand_total'] ?? 0)),
                $this->formatCurrency((float) ($sale['paid_total'] ?? 0)),
                $this->formatCurrency((float) ($sale['returned_total'] ?? 0)),
                $this->formatCurrency((float) ($sale['net_total'] ?? 0)),
                $this->formatCurrency((float) ($sale['balance_due'] ?? 0)),
                $this->paymentStatusLabel(is_string($sale['payment_status'] ?? null) ? $sale['payment_status'] : null),
                $this->formatDateTime(is_string($sale['sold_at'] ?? null) ? $sale['sold_at'] : ''),
            ];
        }

        return $this->renderRows($rows);
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderProductSalesReport(array $report): string
    {
        $rows = [
            [
                'Producto',
                'SKU',
                'Ventas',
                'Cantidad',
                'Precio promedio',
                'Total',
                'Ultima venta',
            ],
        ];

        foreach ($report['product_sales'] ?? [] as $product) {
            if (! is_array($product)) {
                continue;
            }

            $rows[] = [
                (string) ($product['name'] ?? 'Sin producto'),
                is_string($product['sku'] ?? null) ? $product['sku'] : '',
                (string) ($product['sales_count'] ?? 0),
                $this->formatNumber((float) ($product['quantity'] ?? 0)),
                $this->formatCurrency((float) ($product['average_unit_price'] ?? 0)),
                $this->formatCurrency((float) ($product['total'] ?? 0)),
                $this->formatDateTime(is_string($product['last_sold_at'] ?? null) ? $product['last_sold_at'] : ''),
            ];
        }

        return $this->renderRows($rows);
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderProductCatalog(array $report): string
    {
        $rows = [
            [
                'Producto',
                'SKU',
                'Codigo de barras',
                'Categoria',
                'Unidad',
                'Precio venta',
                'Costo',
                'Stock actual',
                'Stock minimo',
                'Control stock',
                'Estado',
                'Ultima actualizacion',
            ],
        ];

        foreach ($report['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rows[] = [
                (string) ($item['name'] ?? 'Sin producto'),
                is_string($item['sku'] ?? null) ? $item['sku'] : '',
                is_string($item['barcode'] ?? null) ? $item['barcode'] : '',
                is_string($item['category_name'] ?? null) ? $item['category_name'] : 'Sin categoria',
                (string) ($item['unit'] ?? 'unidad'),
                $this->formatCurrency((float) ($item['sale_price'] ?? 0)),
                $this->formatCurrency((float) ($item['cost_price'] ?? 0)),
                $this->formatNumber((float) ($item['current_stock'] ?? 0)),
                $this->formatNumber((float) ($item['minimum_stock'] ?? 0)),
                (bool) ($item['track_stock'] ?? false) ? 'Si' : 'No',
                $this->catalogProductStatusLabel($item),
                $this->formatDateTime(is_string($item['updated_at'] ?? null) ? $item['updated_at'] : ''),
            ];
        }

        return $this->renderRows($rows);
    }

    /**
     * @param array<string, mixed> $report
     */
    public function renderAssetCatalog(array $report): string
    {
        $rows = [
            [
                'Activo',
                'Codigo',
                'Categoria',
                'Cantidad',
                'Costo adquisicion',
                'Estado',
                'Fecha adquisicion',
                'Ultima actualizacion',
            ],
        ];

        foreach ($report['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rows[] = [
                (string) ($item['name'] ?? 'Sin activo'),
                is_string($item['code'] ?? null) ? $item['code'] : '',
                is_string($item['category_name'] ?? null) ? $item['category_name'] : 'Sin categoria',
                $this->formatNumber((float) ($item['quantity'] ?? 0)),
                $item['acquisition_cost'] !== null
                    ? $this->formatCurrency((float) ($item['acquisition_cost'] ?? 0))
                    : '',
                $this->catalogAssetStatusLabel(is_string($item['status'] ?? null) ? $item['status'] : ''),
                is_string($item['acquired_at'] ?? null) ? $item['acquired_at'] : '',
                $this->formatDateTime(is_string($item['updated_at'] ?? null) ? $item['updated_at'] : ''),
            ];
        }

        return $this->renderRows($rows);
    }

    /**
     * @param array<string, mixed> $report
     */
    public function salesFilename(array $report): string
    {
        [$dateFrom, $dateTo] = $this->resolveRange($report);

        return sprintf(
            'ventas-rango-%s-a-%s.csv',
            $this->safeFilenameSegment($dateFrom),
            $this->safeFilenameSegment($dateTo),
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    public function productSalesFilename(array $report): string
    {
        [$dateFrom, $dateTo] = $this->resolveRange($report);

        return sprintf(
            'ventas-productos-%s-a-%s.csv',
            $this->safeFilenameSegment($dateFrom),
            $this->safeFilenameSegment($dateTo),
        );
    }

    /**
     * @param array<string, mixed> $report
     */
    public function productCatalogFilename(array $report): string
    {
        return 'catalogo-productos'.$this->searchSuffix($report).'.csv';
    }

    /**
     * @param array<string, mixed> $report
     */
    public function assetCatalogFilename(array $report): string
    {
        return 'catalogo-activos'.$this->searchSuffix($report).'.csv';
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function renderRows(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if (! is_resource($stream)) {
            return '';
        }

        fwrite($stream, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return is_string($contents) ? $contents : '';
    }

    /**
     * @param array<string, mixed> $report
     * @return array{0: string, 1: string}
     */
    private function resolveRange(array $report): array
    {
        $range = is_array($report['range'] ?? null) ? $report['range'] : [];

        return [
            (string) ($range['date_from'] ?? 'sin-rango'),
            (string) ($range['date_to'] ?? 'sin-rango'),
        ];
    }

    /**
     * @param mixed $methods
     */
    private function paymentMethodsLabel(mixed $methods): string
    {
        if (! is_array($methods) || $methods === []) {
            return 'Sin registros';
        }

        return implode(' | ', array_map(
            fn (mixed $method): string => $this->methodLabel(is_string($method) ? $method : ''),
            $methods,
        ));
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'check' => 'Cheque',
            'credit' => 'Cuenta por cobrar',
            default => $method === '' ? 'Sin registros' : $method,
        };
    }

    private function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Pagada',
            'partial' => 'Parcial',
            'credit' => 'Credito',
            default => 'Pendiente',
        };
    }

    private function documentLabel(?string $documentType): string
    {
        return match ($documentType) {
            'factura' => 'Factura',
            'nota' => 'Nota de venta',
            default => 'Ticket',
        };
    }

    /**
     * @param mixed $item
     */
    private function catalogProductStatusLabel(mixed $item): string
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

    private function catalogAssetStatusLabel(string $status): string
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
        return number_format($value, 2, '.', '');
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 2, '.', '');
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

    /**
     * @param array<string, mixed> $report
     */
    private function searchSuffix(array $report): string
    {
        $search = trim((string) ($report['search'] ?? ''));

        return $search === '' ? '' : '-'.$this->safeFilenameSegment($search);
    }
}
