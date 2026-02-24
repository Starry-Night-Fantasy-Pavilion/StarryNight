<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIChannel
{
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}ai_channels` WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('AIChannel::find error: ' . $e->getMessage());
            return null;
        }
    }

    public static function all(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "SELECT * FROM `{$prefix}ai_channels` ORDER BY priority DESC, weight DESC, id DESC";
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('AIChannel::all error: ' . $e->getMessage());
            return [];
        }
    }

    public static function save(int $id, array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $data['model_group'] = trim((string)($data['model_group'] ?? ''));
        if ($data['model_group'] === '') {
            $data['model_group'] = null;
        }

        $data['config_json'] = self::normalizeJson($data['config_json'] ?? '');
        $data['base_url'] = $data['base_url'] !== '' ? $data['base_url'] : null;
        $data['api_key'] = $data['api_key'] !== '' ? $data['api_key'] : null;
        $data['models_text'] = trim((string)($data['models_text'] ?? ''));
        if ($data['models_text'] === '') {
            $data['models_text'] = null;
        }

        if ($id > 0) {
            $sql = "UPDATE `{$prefix}ai_channels`
                    SET name = :name,
                        type = :type,
                        model_group = :model_group,
                        status = :status,
                        priority = :priority,
                        weight = :weight,
                        base_url = :base_url,
                        api_key = :api_key,
                        models_text = :models_text,
                        config_json = :config_json,
                        concurrency_limit = :concurrency_limit,
                        is_free = :is_free,
                        is_user_custom = :is_user_custom
                    WHERE id = :id";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                ':name' => $data['name'],
                ':type' => $data['type'],
                ':model_group' => $data['model_group'],
                ':status' => $data['status'],
                ':priority' => (int)$data['priority'],
                ':weight' => (int)$data['weight'],
                ':base_url' => $data['base_url'],
                ':api_key' => $data['api_key'],
                ':models_text' => $data['models_text'],
                ':config_json' => $data['config_json'],
                ':concurrency_limit' => (int)$data['concurrency_limit'],
                ':is_free' => (int)$data['is_free'],
                ':is_user_custom' => (int)$data['is_user_custom'],
                ':id' => $id,
            ]);
                return $id;
            } catch (\Throwable $e) {
                error_log('AIChannel::save(update) error: ' . $e->getMessage());
                return $id;
            }
        }

        $sql = "INSERT INTO `{$prefix}ai_channels`
                (name, type, model_group, status, priority, weight, base_url, api_key, models_text, config_json, concurrency_limit, is_free, is_user_custom)
                VALUES
                (:name, :type, :model_group, :status, :priority, :weight, :base_url, :api_key, :models_text, :config_json, :concurrency_limit, :is_free, :is_user_custom)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':model_group' => $data['model_group'],
            ':status' => $data['status'],
            ':priority' => (int)$data['priority'],
            ':weight' => (int)$data['weight'],
            ':base_url' => $data['base_url'],
            ':api_key' => $data['api_key'],
            ':models_text' => $data['models_text'],
            ':config_json' => $data['config_json'],
            ':concurrency_limit' => (int)$data['concurrency_limit'],
            ':is_free' => (int)$data['is_free'],
            ':is_user_custom' => (int)$data['is_user_custom'],
        ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('AIChannel::save(insert) error: ' . $e->getMessage());
            return 0;
        }
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}ai_channels` WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('AIChannel::delete error: ' . $e->getMessage());
            return false;
        }
    }

    public static function maskKey(?string $apiKey): string
    {
        if (!$apiKey) {
            return '';
        }
        $len = strlen($apiKey);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        return substr($apiKey, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($apiKey, -4);
    }

    public static function stats(int $channelId = 0, int $hours = 24): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $where = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            $params = [':hours' => $hours];
            if ($channelId > 0) {
                $where .= " AND channel_id = :channel_id";
                $params[':channel_id'] = $channelId;
            }

            $sql = "SELECT
                        COUNT(*) AS total_calls,
                        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) AS success_calls,
                        ROUND(AVG(CASE WHEN latency_ms IS NULL THEN NULL ELSE latency_ms END), 2) AS avg_latency_ms,
                        MAX(latency_ms) AS max_latency_ms
                    FROM `{$prefix}ai_channel_call_logs`
                    {$where}";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $total = (int)($row['total_calls'] ?? 0);
            $success = (int)($row['success_calls'] ?? 0);
            $row['success_rate'] = $total > 0 ? round(($success / $total) * 100, 2) : 0;

            return $row;
        } catch (\Throwable $e) {
            error_log('AIChannel::stats error: ' . $e->getMessage());
            return [
                'total_calls' => 0,
                'success_calls' => 0,
                'avg_latency_ms' => null,
                'max_latency_ms' => null,
                'success_rate' => 0,
            ];
        }
    }

    public static function recentErrors(int $channelId = 0, int $limit = 50): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $where = "WHERE success = 0";
            $params = [];
            if ($channelId > 0) {
                $where .= " AND channel_id = :channel_id";
                $params[':channel_id'] = $channelId;
            }

            $sql = "SELECT l.*, c.name AS channel_name
                    FROM `{$prefix}ai_channel_call_logs` l
                    LEFT JOIN `{$prefix}ai_channels` c ON c.id = l.channel_id
                    {$where}
                    ORDER BY l.id DESC
                    LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('AIChannel::recentErrors error: ' . $e->getMessage());
            return [];
        }
    }

    private static function normalizeJson(string $maybeJson): ?string
    {
        $maybeJson = trim($maybeJson);
        if ($maybeJson === '') {
            return null;
        }
        $decoded = json_decode($maybeJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 允许用户暂时填入非JSON，避免保存失败
            return $maybeJson;
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

