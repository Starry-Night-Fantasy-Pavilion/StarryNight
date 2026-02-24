<?php

namespace app\models;

use app\services\Database;
use PDO;

class AnnouncementCategory
{
    public static function getAll(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->query("SELECT * FROM `{$prefix}announcement_categories` ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}announcement_categories` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $data = ['name' => $data['name'] ?? '', 'sort_order' => (int)($data['sort_order'] ?? 0)];
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}announcement_categories` (name, sort_order) VALUES (:name, :sort_order)");
        $stmt->execute($data);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}announcement_categories` SET name = :name, sort_order = :sort_order WHERE id = :id");
        return $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':sort_order' => (int)($data['sort_order'] ?? 0),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}announcement_categories` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
