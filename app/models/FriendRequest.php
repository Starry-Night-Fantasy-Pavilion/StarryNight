<?php

namespace app\models;

use app\services\Database;
use PDO;

class FriendRequest
{
    public static function searchUsers(int $currentUserId, string $q, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(30, $limit));

        try {
            // 支持：按 ID 精确、按 username/nickname 模糊
            $isId = ctype_digit($q);
            $sql = "SELECT id, username, nickname
                    FROM `{$prefix}users`
                    WHERE id <> :me AND status = 'active' AND (
                        " . ($isId ? "id = :qid OR " : "") . "
                        username LIKE :kw OR nickname LIKE :kw
                    )
                    ORDER BY 
                        CASE 
                            WHEN id = :qid_exact THEN 1
                            WHEN username = :exact THEN 2
                            WHEN nickname = :exact THEN 3
                            WHEN username LIKE :kw_start THEN 4
                            WHEN nickname LIKE :kw_start THEN 5
                            ELSE 6
                        END,
                        id DESC
                    LIMIT {$limit}";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':me' => $currentUserId,
                ':kw' => '%' . $q . '%',
                ':kw_start' => $q . '%',
                ':exact' => $q,
                ':qid_exact' => $isId ? (int)$q : 0,
            ];
            if ($isId) {
                $params[':qid'] = (int)$q;
            } else {
                $params[':qid'] = 0;
            }
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('FriendRequest::searchUsers error: ' . $e->getMessage());
            return [];
        }
    }

    public static function send(int $requesterId, int $receiverId, string $message = ''): array
    {
        if ($requesterId <= 0 || $receiverId <= 0 || $requesterId === $receiverId) {
            return ['ok' => false, 'message' => '参数无效'];
        }

        if (Friend::areFriends($requesterId, $receiverId)) {
            return ['ok' => true, 'message' => '你们已经是好友了'];
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $message = trim($message);
        if (mb_strlen($message, 'UTF-8') > 255) {
            $message = mb_substr($message, 0, 255, 'UTF-8');
        }

        try {
            $sql = "INSERT INTO `{$prefix}friend_requests` (requester_id, receiver_id, status, message, created_at, updated_at)
                    VALUES (:rid, :eid, 'pending', :msg, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        status = IF(status='pending', status, 'pending'),
                        message = VALUES(message),
                        updated_at = NOW()";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':rid' => $requesterId,
                ':eid' => $receiverId,
                ':msg' => $message,
            ]);
            return ['ok' => true, 'message' => '好友申请已发送'];
        } catch (\Throwable $e) {
            error_log('FriendRequest::send error: ' . $e->getMessage());
            return ['ok' => false, 'message' => '发送失败（可能未执行好友系统迁移 022）'];
        }
    }

    public static function getIncoming(int $userId, string $status = 'pending', int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $limit = max(1, min(50, $limit));
        try {
            $sql = "SELECT r.id, r.requester_id, r.receiver_id, r.status, r.message, r.created_at,
                           u.username, u.nickname
                    FROM `{$prefix}friend_requests` r
                    INNER JOIN `{$prefix}users` u ON u.id = r.requester_id
                    WHERE r.receiver_id = :uid AND r.status = :st
                    ORDER BY r.created_at DESC
                    LIMIT {$limit}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId, ':st' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getOutgoing(int $userId, string $status = 'pending', int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $limit = max(1, min(50, $limit));
        try {
            $sql = "SELECT r.id, r.requester_id, r.receiver_id, r.status, r.message, r.created_at,
                           u.username, u.nickname
                    FROM `{$prefix}friend_requests` r
                    INNER JOIN `{$prefix}users` u ON u.id = r.receiver_id
                    WHERE r.requester_id = :uid AND r.status = :st
                    ORDER BY r.created_at DESC
                    LIMIT {$limit}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId, ':st' => $status]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function respond(int $receiverId, int $requestId, string $action): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        if (!in_array($action, ['accept', 'reject'], true)) {
            return ['ok' => false, 'message' => '操作无效'];
        }
        try {
            $sql = "SELECT * FROM `{$prefix}friend_requests` WHERE id = :id AND receiver_id = :uid LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $requestId, ':uid' => $receiverId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['ok' => false, 'message' => '申请不存在'];
            }
            if (($row['status'] ?? '') !== 'pending') {
                return ['ok' => true, 'message' => '已处理过'];
            }

            if ($action === 'reject') {
                $upd = $pdo->prepare("UPDATE `{$prefix}friend_requests` SET status='rejected', updated_at=NOW() WHERE id=:id");
                $upd->execute([':id' => $requestId]);
                return ['ok' => true, 'message' => '已拒绝'];
            }

            // accept
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE `{$prefix}friend_requests` SET status='accepted', updated_at=NOW() WHERE id=:id");
            $upd->execute([':id' => $requestId]);
            Friend::addMutual((int)$row['requester_id'], (int)$row['receiver_id']);
            $pdo->commit();
            return ['ok' => true, 'message' => '已同意，成为好友'];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('FriendRequest::respond error: ' . $e->getMessage());
            return ['ok' => false, 'message' => '处理失败（可能未执行好友系统迁移 022）'];
        }
    }
}

