<?php

declare(strict_types=1);

namespace VentasPos\Domain\Sales;

use PDO;

final class SalesRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingPreSales(): array
    {
        return $this->pdo->query('SELECT * FROM preventa ORDER BY idPreventa ASC')->fetchAll();
    }
}
