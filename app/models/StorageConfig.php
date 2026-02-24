<?php

namespace app\models;

use app\services\Database;
use PDO;

class StorageConfig
{
    public static function getAll(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}storage_configs` ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getActive(): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}storage_configs` WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByType(string $storageType): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}storage_configs` WHERE storage_type = :storage_type LIMIT 1");
        $stmt->execute([':storage_type' => $storageType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $cols = ['storage_type', 'config_name', 'config_value', 'is_active'];
        $data = array_intersect_key($data, array_flip($cols));
        $data['is_active'] = $data['is_active'] ?? 0;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}storage_configs` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $allowed = ['storage_type', 'config_name', 'config_value', 'is_active'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $data[$col];
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}storage_configs` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function setActive(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 先将所有配置设为非激活状态
        $stmt = $pdo->prepare("UPDATE `{$prefix}storage_configs` SET is_active = 0");
        $stmt->execute();
        
        // 再将指定配置设为激活状态
        $stmt = $pdo->prepare("UPDATE `{$prefix}storage_configs` SET is_active = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}storage_configs` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function getConfigValue(string $storageType): ?array
    {
        $config = self::findByType($storageType);
        if (!$config) {
            return null;
        }
        
        $configValue = json_decode($config['config_value'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $configValue;
    }
}