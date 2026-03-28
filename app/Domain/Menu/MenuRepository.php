<?php

declare(strict_types=1);

namespace VentasPos\Domain\Menu;

use PDO;

final class MenuRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM menu')->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function byAccess(string $access): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM menu WHERE acceso = :access');
        $statement->execute(['access' => $access]);

        return $statement->fetchAll();
    }
}
