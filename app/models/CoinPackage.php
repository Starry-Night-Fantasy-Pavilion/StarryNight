<?php

namespace app\models;

use app\services\Database;
use PDO;

class CoinPackage
{
    public static function getAll(?string $saleStatus = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $sql = "SELECT * FROM `{$prefix}coin_packages` WHERE 1=1";
        $params = [];
        if ($saleStatus !== null) {
            $sql .= " AND sale_status = :sale_status";
            $params[':sale_status'] = $saleStatus;
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}coin_packages` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $cols = ['name', 'amount', 'coin_amount', 'valid_days', 'sale_status', 'is_limited_offer', 'offer_start_at', 'offer_end_at', 'sort_order'];
        $data = array_intersect_key($data, array_flip($cols));
        $data['sale_status'] = $data['sale_status'] ?? 'on_sale';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_limited_offer'] = $data['is_limited_offer'] ?? 0;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}coin_packages` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v === '' ? null : $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $allowed = ['name', 'amount', 'coin_amount', 'valid_days', 'sale_status', 'is_limited_offer', 'offer_start_at', 'offer_end_at', 'sort_order'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $data[$col] === '' ? null : $data[$col];
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}coin_packages` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}coin_packages` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
