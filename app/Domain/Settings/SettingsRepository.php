<?php

declare(strict_types=1);

namespace VentasPos\Domain\Settings;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function alert(): ?array
    {
        $statement = $this->pdo->query('SELECT * FROM alerta LIMIT 1');
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function invoiceData(): array
    {
        return $this->pdo->query('SELECT * FROM datos')->fetchAll();
    }
}
