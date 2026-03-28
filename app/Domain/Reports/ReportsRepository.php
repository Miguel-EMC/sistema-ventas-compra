<?php

declare(strict_types=1);

namespace VentasPos\Domain\Reports;

use PDO;

final class ReportsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function consolidatedSalesByMonth(): array
    {
        $sql = "SELECT SUM(cantidad * precio) AS total, MONTH(fechaVenta) AS month_number
                FROM datosventatotal
                WHERE estado = 'Consolidado'
                GROUP BY MONTH(fechaVenta)
                ORDER BY MONTH(fechaVenta) ASC";

        return $this->pdo->query($sql)->fetchAll();
    }
}
