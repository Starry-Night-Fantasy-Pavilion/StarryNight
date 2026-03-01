<?php

namespace app\models;

use app\services\Database;
use PDO;

class Friend
{
    public static function getFriends(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // friends 表可能未迁移：捕获异常，返回空列表，避免前台 500
        try {
            $sql = "SELECT f.friend_id AS id,
                           u.username,
                           u.nickname
                    FROM `{$prefix}friends` f
                    INNER JOIN `{$prefix}users` u ON u.id = f.friend_id
                    WHERE f.user_id = :uid
                    ORDER BY COALESCE(u.nickname, u.username) ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('Friend::getFriends error: ' . $e->getMessage());
            return [];
        }
    }

    public static function areFriends(int $userId, int $friendId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $sql = "SELECT 1 FROM `{$prefix}friends` WHERE user_id = :uid AND friend_id = :fid LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function addMutual(int $userId, int $friendId): bool
    {
        if ($userId <= 0 || $friendId <= 0 || $userId === $friendId) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();
        try {
            $pdo->beginTransaction();
            $sql = "INSERT IGNORE INTO `{$prefix}friends` (user_id, friend_id, created_at) VALUES (:u1, :u2, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':u1' => $userId, ':u2' => $friendId]);
            $stmt->execute([':u1' => $friendId, ':u2' => $userId]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Friend::addMutual error: ' . $e->getMessage());
            return false;
        }
    }
}

