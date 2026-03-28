<?php

declare(strict_types=1);

namespace VentasPos\Domain\Catalog;

use PDO;

final class CatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function productTypes(): array
    {
        return $this->pdo->query('SELECT * FROM tipoproducto')->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function providers(): array
    {
        return $this->pdo->query('SELECT * FROM proveedor')->fetchAll();
    }
}
