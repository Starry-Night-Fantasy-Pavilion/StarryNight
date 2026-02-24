<?php

namespace app\models;

use app\services\Database;
use PDO;

class AdminRole
{
    public static function getAll(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->query("SELECT * FROM `{$prefix}admin_roles` ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}admin_roles` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $data['is_system'] = $data['is_system'] ?? 0;
        $data['data_scope'] = $data['data_scope'] ?? 'all';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $cols = ['name', 'description', 'is_system', 'data_scope', 'sort_order'];
        $data = array_intersect_key($data, array_flip($cols));
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}admin_roles` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $allowed = ['name', 'description', 'data_scope', 'sort_order'];
        $updates = [];
        $params = [':id' => $id];
        foreach ($allowed as $col) {
            if (!array_key_exists($col, $data)) continue;
            $updates[] = "`{$col}` = :{$col}";
            $params[":{$col}"] = $data[$col];
        }
        if (empty($updates)) return true;
        $sql = "UPDATE `{$prefix}admin_roles` SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}admin_roles` WHERE id = :id AND is_system = 0");
        return $stmt->execute([':id' => $id]);
    }

    public static function getPermissions(int $roleId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT permission_key FROM `{$prefix}admin_role_permissions` WHERE role_id = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public static function setPermissions(int $roleId, array $permissionKeys): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $pdo->prepare("DELETE FROM `{$prefix}admin_role_permissions` WHERE role_id = ?")->execute([$roleId]);
        if (empty($permissionKeys)) return true;
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}admin_role_permissions` (role_id, permission_key) VALUES (?, ?)");
        foreach ($permissionKeys as $key) {
            if (trim($key) === '') continue;
            $stmt->execute([$roleId, trim($key)]);
        }
        return true;
    }

    public static function getAdminRoleIds(int $adminId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT role_id FROM `{$prefix}admin_admin_roles` WHERE admin_id = :admin_id");
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public static function setAdminRoles(int $adminId, array $roleIds): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $pdo->prepare("DELETE FROM `{$prefix}admin_admin_roles` WHERE admin_id = ?")->execute([$adminId]);
        foreach ($roleIds as $rid) {
            $rid = (int) $rid;
            if ($rid <= 0) continue;
            $pdo->prepare("INSERT INTO `{$prefix}admin_admin_roles` (admin_id, role_id) VALUES (?, ?)")->execute([$adminId, $rid]);
        }
        return true;
    }
}
