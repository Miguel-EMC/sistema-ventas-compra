<?php

declare(strict_types=1);

namespace VentasPos\Domain\Auth;

use PDO;

final class AuthRepository
{
    private ?bool $passwordHashColumn = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByLogin(string $login): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM usuarios WHERE login = :login LIMIT 1');
        $statement->execute(['login' => $login]);

        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM usuarios WHERE id_usu = :id LIMIT 1');
        $statement->execute(['id' => $userId]);

        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function passwordHashColumnExists(): bool
    {
        if ($this->passwordHashColumn !== null) {
            return $this->passwordHashColumn;
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $statement = $this->pdo->query("PRAGMA table_info('usuarios')");
            $columns = $statement->fetchAll();
            $this->passwordHashColumn = false;

            foreach ($columns as $column) {
                if (($column['name'] ?? null) === 'password_hash') {
                    $this->passwordHashColumn = true;
                    break;
                }
            }

            return $this->passwordHashColumn;
        }

        $statement = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'password_hash'");
        $this->passwordHashColumn = $statement->fetch() !== false;

        return $this->passwordHashColumn;
    }

    public function migrateLegacyPassword(int $userId, string $plainPassword, bool $clearLegacyPassword = true): void
    {
        if (!$this->passwordHashColumnExists()) {
            return;
        }

        $sql = 'UPDATE usuarios SET password_hash = :password_hash';
        if ($clearLegacyPassword) {
            $sql .= ", password = ''";
        }
        $sql .= ' WHERE id_usu = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);
    }
}
