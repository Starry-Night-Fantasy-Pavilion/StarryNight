<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIModelPrice
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}ai_model_prices` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function allWithChannel(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT p.*, c.name AS channel_name, c.type AS channel_type
                FROM `{$prefix}ai_model_prices` p
                LEFT JOIN `{$prefix}ai_channels` c ON c.id = p.channel_id
                ORDER BY p.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function save(int $id, array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $data['model_name'] = trim((string)($data['model_name'] ?? ''));
        if ($data['model_name'] === '' || (int)$data['channel_id'] <= 0) {
            return $id;
        }

        if ($id > 0) {
            $sql = "UPDATE `{$prefix}ai_model_prices`
                    SET channel_id = :channel_id,
                        model_name = :model_name,
                        input_coin_per_1k = :input_coin_per_1k,
                        output_coin_per_1k = :output_coin_per_1k,
                        profit_percent = :profit_percent,
                        is_active = :is_active
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':channel_id' => (int)$data['channel_id'],
                ':model_name' => $data['model_name'],
                ':input_coin_per_1k' => (string)$data['input_coin_per_1k'],
                ':output_coin_per_1k' => (string)$data['output_coin_per_1k'],
                ':profit_percent' => (string)$data['profit_percent'],
                ':is_active' => (int)$data['is_active'],
                ':id' => $id,
            ]);
            return $id;
        }

        $sql = "INSERT INTO `{$prefix}ai_model_prices`
                (channel_id, model_name, input_coin_per_1k, output_coin_per_1k, profit_percent, is_active)
                VALUES
                (:channel_id, :model_name, :input_coin_per_1k, :output_coin_per_1k, :profit_percent, :is_active)
                ON DUPLICATE KEY UPDATE
                    input_coin_per_1k = VALUES(input_coin_per_1k),
                    output_coin_per_1k = VALUES(output_coin_per_1k),
                    profit_percent = VALUES(profit_percent),
                    is_active = VALUES(is_active)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':channel_id' => (int)$data['channel_id'],
            ':model_name' => $data['model_name'],
            ':input_coin_per_1k' => (string)$data['input_coin_per_1k'],
            ':output_coin_per_1k' => (string)$data['output_coin_per_1k'],
            ':profit_percent' => (string)$data['profit_percent'],
            ':is_active' => (int)$data['is_active'],
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}ai_model_prices` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

