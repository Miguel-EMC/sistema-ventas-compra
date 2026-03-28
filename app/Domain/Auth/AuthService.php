<?php

declare(strict_types=1);

namespace VentasPos\Domain\Auth;

final class AuthService
{
    public function __construct(private readonly AuthRepository $repository)
    {
    }

    public function attempt(string $login, string $password): ?array
    {
        $user = $this->repository->findByLogin($login);

        if ($user === null) {
            return null;
        }

        $hash = $user['password_hash'] ?? null;
        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
            return $user;
        }

        if (($user['password'] ?? '') === $password) {
            $this->repository->migrateLegacyPassword((int) $user['id_usu'], $password);

            return $this->repository->findById((int) $user['id_usu']);
        }

        return null;
    }

    public function findById(int $userId): ?array
    {
        return $this->repository->findById($userId);
    }

    public function findByLogin(string $login): ?array
    {
        return $this->repository->findByLogin($login);
    }
}
