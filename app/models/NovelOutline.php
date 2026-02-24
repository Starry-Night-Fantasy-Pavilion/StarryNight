<?php

namespace app\models;

use app\services\Database;
use PDO;

class NovelOutline
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_outlines` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByNovel(int $novelId, ?string $outlineType = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}novel_outlines` WHERE novel_id = :novel_id";
        $params = [':novel_id' => $novelId];

        if ($outlineType) {
            $sql .= " AND outline_type = :outline_type";
            $params[':outline_type'] = $outlineType;
        }

        $sql .= " ORDER BY level ASC, sort_order ASC, id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}novel_outlines` 
                (novel_id, outline_type, title, content, parent_id, level, sort_order)
                VALUES 
                (:novel_id, :outline_type, :title, :content, :parent_id, :level, :sort_order)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':novel_id' => $data['novel_id'],
            ':outline_type' => $data['outline_type'] ?? 'chapter',
            ':title' => $data['title'] ?? null,
            ':content' => is_array($data['content']) ? json_encode($data['content'], JSON_UNESCAPED_UNICODE) : ($data['content'] ?? ''),
            ':parent_id' => $data['parent_id'] ?? null,
            ':level' => (int)($data['level'] ?? 1),
            ':sort_order' => (int)($data['sort_order'] ?? 0),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['title', 'content', 'parent_id', 'level', 'sort_order'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'content' && is_array($data[$field])) {
                    $fields[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                } else {
                    $fields[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}novel_outlines` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $stmt = $pdo->prepare("DELETE FROM `{$prefix}novel_outlines` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function buildTree(array $items): array
    {
        $tree = [];
        $indexed = [];

        // 先建立索引
        foreach ($items as $item) {
            $indexed[$item['id']] = $item;
            $indexed[$item['id']]['children'] = [];
        }

        // 构建树
        foreach ($indexed as $id => $item) {
            if ($item['parent_id'] && isset($indexed[$item['parent_id']])) {
                $indexed[$item['parent_id']]['children'][] = &$indexed[$id];
            } else {
                $tree[] = &$indexed[$id];
            }
        }

        return $tree;
    }
}
