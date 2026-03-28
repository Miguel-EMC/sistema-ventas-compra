<?php

declare(strict_types=1);

namespace VentasPos\Domain\Users;

use PDO;

final class UsersRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM usuarios')->fetchAll();
    }

    public function find(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM usuarios WHERE id_usu = :id LIMIT 1');
        $statement->execute(['id' => $userId]);

        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }
}
