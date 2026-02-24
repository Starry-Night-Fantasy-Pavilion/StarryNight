<?php

namespace app\models;

use app\services\Database;
use PDO;

class UserFeedback
{
    /**
     * 创建用户反馈
     *
     * @param array $data
     * @return int|null
     */
    public static function create(array $data): ?int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "INSERT INTO `{$prefix}user_feedback` 
                    (user_id, type, title, content, attachments, status, created_at) 
                    VALUES 
                    (:user_id, :type, :title, :content, :attachments, :status, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':type' => $data['type'] ?? 'suggestion',
                ':title' => $data['title'] ?? '',
                ':content' => $data['content'] ?? '',
                ':attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
                ':status' => 1 // 待处理
            ]);

            return $pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * 根据ID获取反馈
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT f.*, u.username, u.nickname 
                FROM `{$prefix}user_feedback` f
                LEFT JOIN `{$prefix}user` u ON f.user_id = u.id
                WHERE f.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        return $feedback ?: null;
    }

    /**
     * 获取用户反馈列表
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string|null $type
     * @param int|null $status
     * @return array
     */
    public static function getByUserId(int $userId, int $page = 1, int $perPage = 20, ?string $type = null, ?int $status = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT f.*, u.username, u.nickname 
                FROM `{$prefix}user_feedback` f
                LEFT JOIN `{$prefix}user` u ON f.user_id = u.id
                WHERE f.user_id = :user_id";
        
        $params = [':user_id' => $userId];
        
        if ($type) {
            $sql .= " AND f.type = :type";
            $params[':type'] = $type;
        }
        
        if ($status !== null) {
            $sql .= " AND f.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY f.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}user_feedback` WHERE user_id = :user_id";
        $countParams = [':user_id' => $userId];
        
        if ($type) {
            $countSql .= " AND type = :type";
            $countParams[':type'] = $type;
        }
        
        if ($status !== null) {
            $countSql .= " AND status = :status";
            $countParams[':status'] = $status;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'feedbacks' => $feedbacks,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 更新反馈状态
     *
     * @param int $id
     * @param int $status
     * @param string|null $adminReply
     * @return bool
     */
    public static function updateStatus(int $id, int $status, ?string $adminReply = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "UPDATE `{$prefix}user_feedback` 
                    SET status = :status, admin_reply = :admin_reply, updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':status' => $status,
                ':admin_reply' => $adminReply
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取反馈统计
     *
     * @param int $userId
     * @return array
     */
    public static function getStatistics(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    type,
                    COUNT(*) as count,
                    AVG(CASE WHEN status = 3 THEN 1 ELSE 0 END) * 100 as resolved_rate
                FROM `{$prefix}user_feedback` 
                WHERE user_id = :user_id
                GROUP BY type";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $statistics;
    }

    /**
     * 获取所有反馈（管理员用）
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $type
     * @param int|null $status
     * @return array
     */
    public static function getAll(int $page = 1, int $perPage = 20, ?string $type = null, ?int $status = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT f.*, u.username, u.nickname 
                FROM `{$prefix}user_feedback` f
                LEFT JOIN `{$prefix}user` u ON f.user_id = u.id";
        
        $params = [];
        
        if ($type) {
            $sql .= " WHERE f.type = :type";
            $params[':type'] = $type;
        }
        
        if ($status !== null) {
            $sql .= " AND f.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY f.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}user_feedback` f";
        $countParams = [];
        
        if ($type) {
            $countSql .= " WHERE type = :type";
            $countParams[':type'] = $type;
        }
        
        if ($status !== null) {
            $countSql .= " WHERE status = :status";
            $countParams[':status'] = $status;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'feedbacks' => $feedbacks,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 删除反馈
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "DELETE FROM `{$prefix}user_feedback` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取反馈类型列表
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            'suggestion' => '功能建议',
            'bug_report' => 'Bug报告',
            'other' => '其他'
        ];
    }

    /**
     * 获取状态列表
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            1 => '待处理',
            2 => '处理中',
            3 => '已解决',
            4 => '已关闭'
        ];
    }
}
