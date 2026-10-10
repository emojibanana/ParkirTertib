<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Data access untuk akun user (FR-001).
 * Semua query memakai PDO prepared statements + bindValue (NFR-001).
 */
class UserModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `user` (`name`, `email`, `password`, `role`)'
            . ' VALUES (:name, :email, :password, :role)'
        );
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE `email` = :email LIMIT 1');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `user` WHERE `id` = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}
