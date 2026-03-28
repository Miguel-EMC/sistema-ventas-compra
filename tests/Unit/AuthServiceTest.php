<?php

declare(strict_types=1);

namespace VentasPos\Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;
use VentasPos\Domain\Auth\AuthRepository;
use VentasPos\Domain\Auth\AuthService;

final class AuthServiceTest extends TestCase
{
    public function testAuthenticatesLegacyUserAndMigratesHash(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE usuarios (id_usu INTEGER PRIMARY KEY AUTOINCREMENT, login TEXT, tipo TEXT, nombre TEXT, password TEXT, password_hash TEXT, foto TEXT)');
        $pdo->exec("INSERT INTO usuarios (login, tipo, nombre, password, password_hash, foto) VALUES ('admin', 'ADMINISTRADOR', 'Admin', 'secret', NULL, 'foto.png')");

        $service = new AuthService(new AuthRepository($pdo));
        $user = $service->attempt('admin', 'secret');

        self::assertNotNull($user);
        self::assertSame('admin', $user['login']);

        $storedHash = $pdo->query("SELECT password_hash FROM usuarios WHERE login = 'admin'")->fetchColumn();
        self::assertIsString($storedHash);
        self::assertNotSame('', $storedHash);
    }
}
