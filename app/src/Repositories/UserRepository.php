<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\HttpException;
use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{id: int, email: string, role: string, password_hash: string, created_at: string}|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, role, password_hash, created_at FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array{id: int, email: string, role: string, password_hash: string}|null */
    public function findByIdWithSecret(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, role, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{id: int, email: string, role: string, created_at: string}
     */
    public function create(string $email, string $passwordHash, string $role = 'user'): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)'
            );
            $stmt->execute([$email, $passwordHash, $role]);
        } catch (\PDOException) {
            throw new HttpException('Email is already registered', 409);
        }

        $id = (int) $this->pdo->lastInsertId();

        return [
            'id' => $id,
            'email' => $email,
            'role' => $role,
            'created_at' => date('c'),
        ];
    }

    public function verifyPassword(string $hash, string $plain): bool
    {
        return password_verify($plain, $hash);
    }

    /** @return array{id: int, email: string, role: string, created_at: string}|null */
    public function findProfile(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, role, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
