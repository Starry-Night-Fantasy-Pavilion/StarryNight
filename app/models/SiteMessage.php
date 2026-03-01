<?php

namespace app\models;

use app\services\Database;
use PDO;

class SiteMessage
{
    /**
     * 获取站内信（支持 user_id=NULL 作为“系统广播”）
     */
    public static function getUserMessages(int $userId, int $page = 1, int $perPage = 20, array $keywords = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["(user_id = :uid OR user_id IS NULL)"];
        $params = [':uid' => $userId];

        $kwParts = [];
        foreach ($keywords as $i => $kw) {
            $kw = trim((string)$kw);
            if ($kw === '') continue;
            $p = ":kw{$i}";
            $kwParts[] = "(title LIKE {$p} OR content LIKE {$p})";
            $params[$p] = '%' . $kw . '%';
        }
        if (!empty($kwParts)) {
            $where[] = '(' . implode(' OR ', $kwParts) . ')';
        }

        $whereSql = implode(' AND ', $where);

        try {
            $countSql = "SELECT COUNT(*) FROM `{$prefix}site_messages` WHERE {$whereSql}";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = "SELECT id, user_id, title, content, status, created_at
                    FROM `{$prefix}site_messages`
                    WHERE {$whereSql}
                    ORDER BY id DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int)ceil($total / $perPage),
            ];
        } catch (\Throwable $e) {
            error_log('SiteMessage::getUserMessages error: ' . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
            ];
        }
    }

    public static function markAsRead(int $userId, int $messageId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "UPDATE `{$prefix}site_messages`
                    SET status = 'read'
                    WHERE id = :id AND (user_id = :uid OR user_id IS NULL)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $messageId, ':uid' => $userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getUnreadCount(int $userId, array $keywords = []): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $where = ["(user_id = :uid OR user_id IS NULL)", "status = 'unread'"];
        $params = [':uid' => $userId];

        $kwParts = [];
        foreach ($keywords as $i => $kw) {
            $kw = trim((string)$kw);
            if ($kw === '') continue;
            $p = ":kw{$i}";
            $kwParts[] = "(title LIKE {$p} OR content LIKE {$p})";
            $params[$p] = '%' . $kw . '%';
        }
        if (!empty($kwParts)) {
            $where[] = '(' . implode(' OR ', $kwParts) . ')';
        }

        $whereSql = implode(' AND ', $where);

        try {
            $sql = "SELECT COUNT(*) FROM `{$prefix}site_messages` WHERE {$whereSql}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

