<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIPresetModel
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}ai_preset_models` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allWithChannel(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT m.*, c.name AS channel_name
                FROM `{$prefix}ai_preset_models` m
                LEFT JOIN `{$prefix}ai_channels` c ON c.id = m.default_channel_id
                ORDER BY m.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function save(int $id, array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $data['name'] = trim((string)($data['name'] ?? ''));
        if ($data['name'] === '') {
            return $id;
        }

        $defaultChannelId = (int)($data['default_channel_id'] ?? 0);
        if ($defaultChannelId <= 0) {
            $defaultChannelId = null;
        }

        if ($id > 0) {
            $sql = "UPDATE `{$prefix}ai_preset_models`
                    SET name = :name,
                        description = :description,
                        default_channel_id = :default_channel_id,
                        is_enabled = :is_enabled
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':default_channel_id' => $defaultChannelId,
                ':is_enabled' => (int)($data['is_enabled'] ?? 0),
                ':id' => $id,
            ]);
            return $id;
        }

        $sql = "INSERT INTO `{$prefix}ai_preset_models`
                (name, description, default_channel_id, is_enabled)
                VALUES
                (:name, :description, :default_channel_id, :is_enabled)
                ON DUPLICATE KEY UPDATE
                    description = VALUES(description),
                    default_channel_id = VALUES(default_channel_id),
                    is_enabled = VALUES(is_enabled)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':default_channel_id' => $defaultChannelId,
            ':is_enabled' => (int)($data['is_enabled'] ?? 0),
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}ai_preset_models` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

