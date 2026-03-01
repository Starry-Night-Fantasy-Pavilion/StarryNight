<?php

namespace app\models;

use app\services\Database;
use PDO;

class FriendMessage
{
    public static function send(int $senderId, int $receiverId, string $content): array
    {
        $content = trim($content);
        if ($senderId <= 0 || $receiverId <= 0 || $senderId === $receiverId) {
            return ['ok' => false, 'message' => '参数无效'];
        }
        if ($content === '') {
            return ['ok' => false, 'message' => '消息不能为空'];
        }
        if (mb_strlen($content, 'UTF-8') > 2000) {
            $content = mb_substr($content, 0, 2000, 'UTF-8');
        }

        // 仅允许好友之间发消息
        if (!Friend::areFriends($senderId, $receiverId)) {
            return ['ok' => false, 'message' => '你们还不是好友'];
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "INSERT INTO `{$prefix}friend_messages` (sender_id, receiver_id, content, is_read, created_at)
                    VALUES (:sid, :rid, :c, 0, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':sid' => $senderId, ':rid' => $receiverId, ':c' => $content]);
            return ['ok' => true, 'message' => '已发送'];
        } catch (\Throwable $e) {
            error_log('FriendMessage::send error: ' . $e->getMessage());
            return ['ok' => false, 'message' => '发送失败（可能未执行好友系统迁移 022）'];
        }
    }

    public static function getUnreadCount(int $userId): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "SELECT COUNT(*) FROM `{$prefix}friend_messages` WHERE receiver_id = :uid AND is_read = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function markReadBetween(int $userId, int $friendId): void
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "UPDATE `{$prefix}friend_messages`
                    SET is_read = 1, read_at = NOW()
                    WHERE receiver_id = :uid AND sender_id = :fid AND is_read = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * 会话列表：每个好友一条，包含最后一条消息+未读数
     */
    public static function getConversations(int $userId, int $limit = 30): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $limit = max(1, min(50, $limit));
        try {
            // 取与我相关的消息，按对端聚合取最新一条
            $sql = "
                SELECT t.friend_id,
                       u.username,
                       u.nickname,
                       t.last_content,
                       t.last_time,
                       COALESCE(unread.unread_count, 0) AS unread_count
                FROM (
                    SELECT
                        CASE WHEN sender_id = :uid THEN receiver_id ELSE sender_id END AS friend_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(content ORDER BY created_at DESC SEPARATOR '\\n'), '\\n', 1) AS last_content,
                        MAX(created_at) AS last_time
                    FROM `{$prefix}friend_messages`
                    WHERE sender_id = :uid OR receiver_id = :uid
                    GROUP BY friend_id
                    ORDER BY last_time DESC
                    LIMIT {$limit}
                ) t
                INNER JOIN `{$prefix}users` u ON u.id = t.friend_id
                LEFT JOIN (
                    SELECT sender_id AS friend_id, COUNT(*) AS unread_count
                    FROM `{$prefix}friend_messages`
                    WHERE receiver_id = :uid AND is_read = 0
                    GROUP BY sender_id
                ) unread ON unread.friend_id = t.friend_id
                ORDER BY t.last_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('FriendMessage::getConversations error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getChatMessages(int $userId, int $friendId, int $sinceId = 0, int $limit = 50): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $limit = max(1, min(200, $limit));
        try {
            $sql = "SELECT id, sender_id, receiver_id, content, created_at
                    FROM `{$prefix}friend_messages`
                    WHERE id > :since
                      AND ((sender_id = :uid AND receiver_id = :fid) OR (sender_id = :fid AND receiver_id = :uid))
                    ORDER BY id ASC
                    LIMIT {$limit}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':since' => $sinceId, ':uid' => $userId, ':fid' => $friendId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

