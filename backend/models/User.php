<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, username, full_name, role, administration_id, is_active, created_at FROM users ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function create(string $username, string $password, string $fullName, string $role, ?int $administrationId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (username, password_hash, full_name, role, administration_id)
             VALUES (:username, :password_hash, :full_name, :role, :administration_id)'
        );
        $stmt->execute([
            'username'         => $username,
            'password_hash'    => password_hash($password, PASSWORD_DEFAULT),
            'full_name'        => $fullName,
            'role'             => $role,
            'administration_id' => $administrationId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function updatePermissions(int $id, string $role, bool $isActive): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET role = :role, is_active = :is_active WHERE id = :id'
        );
        return $stmt->execute([
            'role'      => $role,
            'is_active' => $isActive ? 1 : 0,
            'id'        => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function usernameExists(string $username): bool
    {
        $stmt = Database::connection()->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return (bool) $stmt->fetch();
    }
}
