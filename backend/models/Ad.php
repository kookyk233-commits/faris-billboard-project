<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Ad
{
    /**
     * يعيد الإعلانات مرتبة من الأحدث، مع دعم صفحات (pagination) بسيطة.
     */
    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT
                ads.id, ads.title, ads.content, ads.source, ads.image_path,
                ads.published_at, ads.created_at,
                administrations.id AS administration_id, administrations.name AS administration_name,
                users.id AS user_id, users.full_name AS user_full_name
             FROM ads
             JOIN administrations ON administrations.id = ads.administration_id
             JOIN users ON users.id = ads.user_id
             ORDER BY ads.published_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM ads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(
        string $title,
        string $content,
        ?string $source,
        ?string $imagePath,
        int $administrationId,
        int $userId
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO ads (title, content, source, image_path, administration_id, user_id)
             VALUES (:title, :content, :source, :image_path, :administration_id, :user_id)'
        );
        $stmt->execute([
            'title'             => $title,
            'content'           => $content,
            'source'            => $source,
            'image_path'        => $imagePath,
            'administration_id' => $administrationId,
            'user_id'           => $userId,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $title, string $content, ?string $source): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE ads SET title = :title, content = :content, source = :source WHERE id = :id'
        );
        return $stmt->execute(['title' => $title, 'content' => $content, 'source' => $source, 'id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM ads WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
