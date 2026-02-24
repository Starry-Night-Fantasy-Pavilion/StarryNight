<?php

namespace app\models;

use app\services\Database;
use PDO;

class Setting
{
    public static function get(string $key, $default = null)
    {
        try {
            $pdo = Database::pdo();
            $prefix = Database::prefix();
            $stmt = $pdo->prepare("SELECT `value` FROM `{$prefix}settings` WHERE `key` = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row['value'] : $default;
        } catch (\Throwable $e) {
            error_log('Setting::get() error: ' . $e->getMessage());
            return $default;
        }
    }

    public static function set(string $key, $value): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $value = is_string($value) ? $value : json_encode($value);
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = :value2");
        return $stmt->execute([':key' => $key, ':value' => $value, ':value2' => $value]);
    }

    public static function getMany(array $keys): array
    {
        if (empty($keys)) return [];
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ({$placeholders})");
        $stmt->execute(array_values($keys));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($keys as $k) $out[$k] = null;
        foreach ($rows as $r) $out[$r['key']] = $r['value'];
        return $out;
    }

    public static function setMany(array $data): bool
    {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
        return true;
    }
}
