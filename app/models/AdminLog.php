<?php

namespace app\models;

use app\services\Database;
use PDO;

class AdminLog
{
    public static function operationLog(?int $adminId, string $module, string $action, ?string $content = null, string $result = 'success', ?string $ip = null, ?string $userAgent = null): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $table = $prefix . 'admin_operation_logs';
        try {
            $stmt = $pdo->prepare("INSERT INTO `{$table}` (admin_id, username, module, action, content, result, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $username = null;
            if ($adminId) {
                $u = $pdo->prepare("SELECT username FROM `{$prefix}admin_admins` WHERE id = ?");
                $u->execute([$adminId]);
                $username = $u->fetchColumn();
            }
            $stmt->execute([
                $adminId,
                $username,
                $module,
                $action,
                $content,
                $result,
                $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ]);
        } catch (\Throwable $e) {
            error_log('AdminLog::operationLog error: ' . $e->getMessage());
        }
    }

    public static function loginLog(?int $adminId, string $username, string $result, ?string $ip = null, ?string $userAgent = null): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $table = $prefix . 'admin_login_logs';
        try {
            $stmt = $pdo->prepare("INSERT INTO `{$table}` (admin_id, username, ip, result, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $adminId,
                $username,
                $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                $result,
                $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ]);
        } catch (\Throwable $e) {
            error_log('AdminLog::loginLog error: ' . $e->getMessage());
        }
    }

    public static function exceptionLog(string $level, string $message, ?array $context = null, ?string $file = null, ?int $line = null): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $table = $prefix . 'admin_exception_logs';
        try {
            $stmt = $pdo->prepare("INSERT INTO `{$table}` (level, message, context_json, file, line) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $level,
                $message,
                $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                $file,
                $line,
            ]);
        } catch (\Throwable $e) {
            error_log('AdminLog::exceptionLog error: ' . $e->getMessage());
        }
    }

    public static function getOperationLogs(int $page = 1, int $perPage = 20, ?int $adminId = null, ?string $module = null, ?string $result = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['1=1'];
        $params = [];
        if ($adminId !== null) { $where[] = 'admin_id = :admin_id'; $params[':admin_id'] = $adminId; }
        if ($module !== null && $module !== '') { $where[] = 'module = :module'; $params[':module'] = $module; }
        if ($result !== null && $result !== '') { $where[] = 'result = :result'; $params[':result'] = $result; }
        $sql = "SELECT * FROM `{$prefix}admin_operation_logs` WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT " . (($page - 1) * $perPage) . ", " . ($perPage + 1);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $perPage;
        if ($hasMore) array_pop($rows);
        return ['list' => $rows, 'hasMore' => $hasMore];
    }

    public static function getLoginLogs(int $page = 1, int $perPage = 20, ?string $username = null, ?string $result = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['1=1'];
        $params = [];
        if ($username !== null && $username !== '') { $where[] = 'username = :username'; $params[':username'] = $username; }
        if ($result !== null && $result !== '') { $where[] = 'result = :result'; $params[':result'] = $result; }
        $sql = "SELECT * FROM `{$prefix}admin_login_logs` WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT " . (($page - 1) * $perPage) . ", " . ($perPage + 1);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $perPage;
        if ($hasMore) array_pop($rows);
        return ['list' => $rows, 'hasMore' => $hasMore];
    }

    public static function getExceptionLogs(int $page = 1, int $perPage = 20, ?string $level = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ['1=1'];
        $params = [];
        if ($level !== null && $level !== '') { $where[] = 'level = :level'; $params[':level'] = $level; }
        $sql = "SELECT * FROM `{$prefix}admin_exception_logs` WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT " . (($page - 1) * $perPage) . ", " . ($perPage + 1);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $perPage;
        if ($hasMore) array_pop($rows);
        return ['list' => $rows, 'hasMore' => $hasMore];
    }
}
