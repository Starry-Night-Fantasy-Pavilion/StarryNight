<?php

namespace app\models;

use app\services\Database;
use PDO;

class NoticeBar
{
    public static function getAll(?string $lang = null, ?string $status = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['1=1'];
        $params = [];
        if ($lang !== null && $lang !== '') {
            $where[] = 'lang = :lang';
            $params[':lang'] = $lang;
        }
        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        try {
            $sql = "SELECT * FROM `{$prefix}notice_bar` WHERE " . implode(' AND ', $where) . " ORDER BY priority DESC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // 如果 priority 等新字段尚未通过迁移添加，降级为按 id 排序，避免 500
            error_log('NoticeBar::getAll error: ' . $e->getMessage());
            $sql = "SELECT * FROM `{$prefix}notice_bar` WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}notice_bar` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        // loop_weight: 预留字段，目前优先使用 priority(1-10) 作为权重
        $cols = ['content', 'link', 'priority', 'loop_weight', 'display_from', 'display_to', 'status', 'lang'];
        $data = array_intersect_key($data, array_flip($cols));
        $data['status'] = $data['status'] ?? 'enabled';
        $data['lang'] = $data['lang'] ?? 'zh-CN';
        // 优先级：允许 0~100，便于后台使用 0/10/60/90 等更直观的权重
        $data['priority'] = (int)($data['priority'] ?? 0);
        if ($data['priority'] < 0) {
            $data['priority'] = 0;
        } elseif ($data['priority'] > 100) {
            $data['priority'] = 100;
        }
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}notice_bar` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v === '' ? null : $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        // loop_weight 同样允许在后台更新
        $allowed = ['content', 'link', 'priority', 'loop_weight', 'display_from', 'display_to', 'status', 'lang'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            if ($col === 'priority') {
                $v = (int)$data[$col];
                if ($v < 0) {
                    $v = 0;
                } elseif ($v > 100) {
                    $v = 100;
                }
                $params[":{$col}"] = $v;
            } else {
                $params[":{$col}"] = $data[$col] === '' ? null : $data[$col];
            }
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}notice_bar` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}notice_bar` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function toggleStatus(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}notice_bar` SET status = IF(status = 'enabled', 'disabled', 'enabled') WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
