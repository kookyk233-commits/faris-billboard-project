<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Administration
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM administrations ORDER BY name ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM administrations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO administrations (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name): bool
    {
        $stmt = Database::connection()->prepare('UPDATE administrations SET name = :name WHERE id = :id');
        return $stmt->execute(['name' => $name, 'id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM administrations WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
