<?php

namespace app\models;

use app\services\Database;
use PDO;

class StorageCleanupLog
{
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $cols = ['cleanup_type', 'files_deleted', 'space_freed', 'execution_time', 'details'];
        $data = array_intersect_key($data, array_flip($cols));
        
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details'], JSON_UNESCAPED_UNICODE);
        }
        
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $cols);
        $sql = "INSERT INTO `{$prefix}storage_cleanup_logs` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(":{$k}", $v);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function getAll(?string $cleanupType = null, ?int $limit = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['1=1'];
        $params = [];
        
        if ($cleanupType !== null) {
            $where[] = 'cleanup_type = :cleanup_type';
            $params[':cleanup_type'] = $cleanupType;
        }
        
        $sql = "SELECT * FROM `{$prefix}storage_cleanup_logs` WHERE " . implode(' AND ', $where) . " ORDER BY executed_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // 解析 details JSON 字段
        foreach ($results as &$result) {
            if (!empty($result['details'])) {
                $result['details'] = json_decode($result['details'], true);
            }
        }
        
        return $results;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}storage_cleanup_logs` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['details'])) {
            $result['details'] = json_decode($result['details'], true);
        }
        
        return $result ?: null;
    }

    public static function getStats(?string $cleanupType = null, ?int $days = 30): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['executed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)'];
        $params = [':days' => $days];
        
        if ($cleanupType !== null) {
            $where[] = 'cleanup_type = :cleanup_type';
            $params[':cleanup_type'] = $cleanupType;
        }
        
        $sql = "
            SELECT 
                cleanup_type,
                COUNT(*) as cleanup_count,
                SUM(files_deleted) as total_files_deleted,
                SUM(space_freed) as total_space_freed,
                AVG(execution_time) as avg_execution_time,
                MAX(executed_at) as last_cleanup
            FROM `{$prefix}storage_cleanup_logs` 
            WHERE " . implode(' AND ', $where) . "
            GROUP BY cleanup_type
            ORDER BY cleanup_type
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getTotalStats(?int $days = 30): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['executed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)'];
        $params = [':days' => $days];
        
        $sql = "
            SELECT 
                COUNT(*) as total_cleanups,
                SUM(files_deleted) as total_files_deleted,
                SUM(space_freed) as total_space_freed,
                AVG(execution_time) as avg_execution_time,
                MAX(executed_at) as last_cleanup
            FROM `{$prefix}storage_cleanup_logs` 
            WHERE " . implode(' AND ', $where) . "
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: [
            'total_cleanups' => 0,
            'total_files_deleted' => 0,
            'total_space_freed' => 0,
            'avg_execution_time' => 0,
            'last_cleanup' => null
        ];
    }

    public static function getRecentCleanups(int $limit = 10): array
    {
        return self::getAll(null, $limit);
    }

    public static function getCleanupsByType(string $cleanupType, int $limit = 10): array
    {
        return self::getAll($cleanupType, $limit);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}storage_cleanup_logs` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function deleteOldLogs(int $days = 90): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}storage_cleanup_logs` WHERE executed_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        return $stmt->rowCount();
    }

    public static function logCleanup(string $cleanupType, int $filesDeleted, int $spaceFreed, float $executionTime, array $details = []): int
    {
        $data = [
            'cleanup_type' => $cleanupType,
            'files_deleted' => $filesDeleted,
            'space_freed' => $spaceFreed,
            'execution_time' => $executionTime,
            'details' => $details
        ];
        
        return self::create($data);
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

    public static function getCleanupTypes(): array
    {
        return [
            'temp_files' => '临时文件',
            'expired_drafts' => '过期草稿',
            'old_logs' => '旧日志',
            'abandoned_resources' => '废弃资源'
        ];
    }

    public static function getCleanupTypeLabel(string $cleanupType): string
    {
        $types = self::getCleanupTypes();
        return $types[$cleanupType] ?? $cleanupType;
    }
}