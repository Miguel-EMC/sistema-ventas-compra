<?php

declare(strict_types=1);

namespace VentasPos\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

final class PdoConnectionFactory
{
    public function make(): PDO
    {
        $host = (string) config('database.connections.mysql.host');
        $port = (int) config('database.connections.mysql.port', 3306);
        $database = (string) config('database.connections.mysql.database');
        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');
        $charset = (string) config('database.connections.mysql.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('No se pudo abrir la conexion PDO: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
