<?php

declare(strict_types=1);

namespace VentasPos\Infrastructure\Database;

use PDO;
use PDOException;

final class MysqliCompat
{
    public string $error = '';
    public int $insert_id = 0;

    private PDO $pdo;

    public function __construct(string $host, string $username, string $password, string $database, int $port = 3306)
    {
        $charset = (string) config('database.connections.mysql.charset', 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function query(string $query): LegacyMysqliResult|bool
    {
        $this->error = '';

        try {
            $statement = $this->pdo->query($query);
            $trimmed = ltrim($query);

            if (preg_match('/^(select|show|describe|explain)\b/i', $trimmed)) {
                /** @var array<int, array<string, mixed>> $rows */
                $rows = $statement->fetchAll();

                return new LegacyMysqliResult($rows);
            }

            $this->insert_id = (int) $this->pdo->lastInsertId();

            return $statement !== false;
        } catch (PDOException $exception) {
            $this->error = $exception->getMessage();

            return false;
        }
    }

    public function real_escape_string(string $value): string
    {
        return substr($this->pdo->quote($value), 1, -1);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
