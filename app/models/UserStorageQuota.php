<?php

namespace app\models;

use app\services\Database;
use PDO;

class UserStorageQuota
{
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}user_storage_quotas` WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createOrUpdate(int $userId, string $membershipLevel, int $totalQuota): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 检查是否已存在记录
        $existing = self::findByUserId($userId);
        
        if ($existing) {
            // 更新现有记录
            $stmt = $pdo->prepare("UPDATE `{$prefix}user_storage_quotas` SET membership_level = :membership_level, total_quota = :total_quota WHERE user_id = :user_id");
            return $stmt->execute([
                ':user_id' => $userId,
                ':membership_level' => $membershipLevel,
                ':total_quota' => $totalQuota
            ]);
        } else {
            // 创建新记录
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}user_storage_quotas` (user_id, membership_level, total_quota, used_space) VALUES (:user_id, :membership_level, :total_quota, 0)");
            return $stmt->execute([
                ':user_id' => $userId,
                ':membership_level' => $membershipLevel,
                ':total_quota' => $totalQuota
            ]);
        }
    }

    public static function updateUsedSpace(int $userId, int $usedSpace): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}user_storage_quotas` SET used_space = :used_space, last_calculated_at = NOW() WHERE user_id = :user_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':used_space' => $usedSpace
        ]);
    }

    public static function addUsedSpace(int $userId, int $additionalSpace): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}user_storage_quotas` SET used_space = used_space + :additional_space, last_calculated_at = NOW() WHERE user_id = :user_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':additional_space' => $additionalSpace
        ]);
    }

    public static function subtractUsedSpace(int $userId, int $spaceToSubtract): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("UPDATE `{$prefix}user_storage_quotas` SET used_space = GREATEST(0, used_space - :space_to_subtract), last_calculated_at = NOW() WHERE user_id = :user_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':space_to_subtract' => $spaceToSubtract
        ]);
    }

    public static function getQuotaByMembershipLevel(string $membershipLevel): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}user_storage_quotas` WHERE membership_level = :membership_level AND user_id = 0 LIMIT 1");
        $stmt->execute([':membership_level' => $membershipLevel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function isOverQuota(int $userId): bool
    {
        $quota = self::findByUserId($userId);
        if (!$quota) {
            return false;
        }
        return $quota['used_space'] > $quota['total_quota'];
    }

    public static function getQuotaPercentage(int $userId): float
    {
        $quota = self::findByUserId($userId);
        if (!$quota || $quota['total_quota'] == 0) {
            return 0;
        }
        return round(($quota['used_space'] / $quota['total_quota']) * 100, 2);
    }

    public static function getRemainingSpace(int $userId): int
    {
        $quota = self::findByUserId($userId);
        if (!$quota) {
            return 0;
        }
        return max(0, $quota['total_quota'] - $quota['used_space']);
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public static function getUsersNearQuotaLimit(float $threshold = 90): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("
            SELECT q.*, u.username, u.email 
            FROM `{$prefix}user_storage_quotas` q 
            JOIN `{$prefix}users` u ON q.user_id = u.id 
            WHERE q.total_quota > 0 
            AND (q.used_space / q.total_quota) * 100 >= :threshold
            ORDER BY (q.used_space / q.total_quota) DESC
        ");
        $stmt->execute([':threshold' => $threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getUsersOverQuota(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("
            SELECT q.*, u.username, u.email 
            FROM `{$prefix}user_storage_quotas` q 
            JOIN `{$prefix}users` u ON q.user_id = u.id 
            WHERE q.used_space > q.total_quota 
            ORDER BY (q.used_space - q.total_quota) DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}