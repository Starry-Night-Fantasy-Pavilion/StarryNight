<?php

namespace app\models;

use app\services\Database;
use PDO;

class MembershipLevel
{
    public static function getAll(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $sql = "SELECT * FROM `{$prefix}membership_levels` ORDER BY id ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}membership_levels` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $allowed = ['name', 'description', 'price_monthly', 'price_yearly', 'benefits_json', 'is_active',
            'coin_discount_percent', 'permissions_json', 'quota_json', 'sort_order'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $data[$col];
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}membership_levels` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $cols = ['name', 'description', 'price_monthly', 'price_yearly', 'benefits_json', 'is_active',
            'coin_discount_percent', 'permissions_json', 'quota_json', 'sort_order'];
        $data = array_intersect_key($data, array_flip($cols));
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['coin_discount_percent'] = $data['coin_discount_percent'] ?? 100;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}membership_levels` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }
}
