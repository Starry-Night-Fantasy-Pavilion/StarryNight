<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIEmbeddingModel
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}ai_embedding_models` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function all(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $sql = "SELECT * FROM `{$prefix}ai_embedding_models` ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function save(int $id, array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            return $id;
        }

        $payload = [
            ':name' => $name,
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? 'openai',
            ':base_url' => ($data['base_url'] ?? '') !== '' ? $data['base_url'] : null,
            ':api_key' => ($data['api_key'] ?? '') !== '' ? $data['api_key'] : null,
            ':config_json' => self::normalizeJson($data['config_json'] ?? ''),
            ':is_enabled' => (int)($data['is_enabled'] ?? 0),
            ':is_user_customizable' => (int)($data['is_user_customizable'] ?? 0),
        ];

        if ($id > 0) {
            $sql = "UPDATE `{$prefix}ai_embedding_models`
                    SET name = :name,
                        description = :description,
                        type = :type,
                        base_url = :base_url,
                        api_key = :api_key,
                        config_json = :config_json,
                        is_enabled = :is_enabled,
                        is_user_customizable = :is_user_customizable
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $payload[':id'] = $id;
            $stmt->execute($payload);
            return $id;
        }

        $sql = "INSERT INTO `{$prefix}ai_embedding_models`
                (name, description, type, base_url, api_key, config_json, is_enabled, is_user_customizable)
                VALUES
                (:name, :description, :type, :base_url, :api_key, :config_json, :is_enabled, :is_user_customizable)
                ON DUPLICATE KEY UPDATE
                    description = VALUES(description),
                    type = VALUES(type),
                    base_url = VALUES(base_url),
                    api_key = VALUES(api_key),
                    config_json = VALUES(config_json),
                    is_enabled = VALUES(is_enabled),
                    is_user_customizable = VALUES(is_user_customizable)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($payload);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}ai_embedding_models` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    private static function normalizeJson(string $maybeJson): ?string
    {
        $maybeJson = trim($maybeJson);
        if ($maybeJson === '') {
            return null;
        }
        $decoded = json_decode($maybeJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $maybeJson;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

