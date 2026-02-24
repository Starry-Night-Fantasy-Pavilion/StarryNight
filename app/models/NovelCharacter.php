<?php

namespace app\models;

use app\services\Database;
use PDO;

class NovelCharacter
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_characters` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByNovel(int $novelId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}novel_characters` WHERE novel_id = :novel_id ORDER BY role_type ASC, id ASC");
        $stmt->execute([':novel_id' => $novelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}novel_characters` 
                (novel_id, name, role_type, age, gender, appearance, personality, background, abilities, motivation, relationships_json, avatar_image)
                VALUES 
                (:novel_id, :name, :role_type, :age, :gender, :appearance, :personality, :background, :abilities, :motivation, :relationships_json, :avatar_image)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':novel_id' => $data['novel_id'],
            ':name' => $data['name'] ?? '',
            ':role_type' => $data['role_type'] ?? 'other',
            ':age' => isset($data['age']) ? (int)$data['age'] : null,
            ':gender' => $data['gender'] ?? 'unknown',
            ':appearance' => $data['appearance'] ?? null,
            ':personality' => is_array($data['personality']) ? json_encode($data['personality'], JSON_UNESCAPED_UNICODE) : ($data['personality'] ?? null),
            ':background' => $data['background'] ?? null,
            ':abilities' => $data['abilities'] ?? null,
            ':motivation' => $data['motivation'] ?? null,
            ':relationships_json' => is_array($data['relationships_json']) ? json_encode($data['relationships_json'], JSON_UNESCAPED_UNICODE) : ($data['relationships_json'] ?? null),
            ':avatar_image' => $data['avatar_image'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = ['name', 'role_type', 'age', 'gender', 'appearance', 'personality', 'background', 'abilities', 'motivation', 'relationships_json', 'avatar_image'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if (in_array($field, ['personality', 'relationships_json']) && is_array($data[$field])) {
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

        $sql = "UPDATE `{$prefix}novel_characters` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $stmt = $pdo->prepare("DELETE FROM `{$prefix}novel_characters` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function getRelationships(int $novelId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   c1.name as character1_name, 
                   c2.name as character2_name
            FROM `{$prefix}novel_character_relationships` r
            LEFT JOIN `{$prefix}novel_characters` c1 ON r.character_id_1 = c1.id
            LEFT JOIN `{$prefix}novel_characters` c2 ON r.character_id_2 = c2.id
            WHERE r.novel_id = :novel_id
        ");
        $stmt->execute([':novel_id' => $novelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveRelationship(int $novelId, int $characterId1, int $characterId2, string $relationshipType, string $description = ''): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 检查是否已存在
        $stmt = $pdo->prepare("SELECT id FROM `{$prefix}novel_character_relationships` 
                              WHERE character_id_1 = :c1 AND character_id_2 = :c2");
        $stmt->execute([':c1' => $characterId1, ':c2' => $characterId2]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 更新
            $stmt = $pdo->prepare("UPDATE `{$prefix}novel_character_relationships` 
                                   SET relationship_type = :type, description = :desc
                                   WHERE id = :id");
            $stmt->execute([
                ':type' => $relationshipType,
                ':desc' => $description,
                ':id' => $existing['id']
            ]);
            return (int)$existing['id'];
        } else {
            // 新建
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}novel_character_relationships` 
                                   (novel_id, character_id_1, character_id_2, relationship_type, description)
                                   VALUES (:novel_id, :c1, :c2, :type, :desc)");
            $stmt->execute([
                ':novel_id' => $novelId,
                ':c1' => $characterId1,
                ':c2' => $characterId2,
                ':type' => $relationshipType,
                ':desc' => $description,
            ]);
            return (int)$pdo->lastInsertId();
        }
    }
}
