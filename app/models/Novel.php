<?php

namespace app\models;

use app\services\Database;
use PDO;

class Novel
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novels` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByUser(int $userId, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $sql = "SELECT * FROM `{$prefix}novels` WHERE user_id = :user_id";
        $params = [':user_id' => $userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['genre'])) {
            $sql .= " AND genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        $sql .= " ORDER BY updated_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}novels` 
                (user_id, title, genre, type, theme, target_words, status, cover_image, description, tags)
                VALUES 
                (:user_id, :title, :genre, :type, :theme, :target_words, :status, :cover_image, :description, :tags)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'] ?? '',
            ':genre' => $data['genre'] ?? null,
            ':type' => $data['type'] ?? null,
            ':theme' => $data['theme'] ?? null,
            ':target_words' => (int)($data['target_words'] ?? 0),
            ':status' => $data['status'] ?? 'draft',
            ':cover_image' => $data['cover_image'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['title', 'genre', 'type', 'theme', 'target_words', 'status', 'cover_image', 'description', 'tags'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}novels` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function updateWordCount(int $id): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}novels` n
                SET n.current_words = (
                    SELECT COALESCE(SUM(c.word_count), 0)
                    FROM `{$prefix}novel_chapters` c
                    WHERE c.novel_id = n.id
                )
                WHERE n.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $stmt = $pdo->prepare("DELETE FROM `{$prefix}novels` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取统计信息
     *
     * @return array
     */
    public static function getStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'published' THEN 1 END) as published,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                    COUNT(CASE WHEN status = 'writing' THEN 1 END) as writing,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    SUM(view_count) as total_views,
                    SUM(favorite_count) as total_favorites,
                    AVG(rating) as avg_rating
                FROM `{$prefix}novels`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
