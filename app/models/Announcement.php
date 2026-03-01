<?php

namespace app\models;

use app\services\Database;
use PDO;

class Announcement
{
    /**
     * 创建公告
     *
     * @param array $data
     * @return int|null
     */
    public static function create(array $data): ?int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "INSERT INTO `{$prefix}announcements` 
                    (title, content, category, is_top, is_popup, status, published_at, created_at) 
                    VALUES 
                    (:title, :content, :category, :is_top, :is_popup, :status, :published_at, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':content' => $data['content'] ?? '',
                ':category' => $data['category'] ?? 'system_update',
                ':is_top' => $data['is_top'] ?? 0,
                ':is_popup' => $data['is_popup'] ?? 0,
                ':status' => $data['status'] ?? 1, // 草稿
                ':published_at' => $data['published_at'] ?? null
            ]);

            return $pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * 根据ID获取公告
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}announcements` WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

        return $announcement ?: null;
    }

    /**
     * 获取公告列表
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $category
     * @param int|null $status
     * @return array
     */
    public static function getList(int $page = 1, int $perPage = 20, ?string $category = null, ?int $status = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM `{$prefix}announcements`";
        $params = [];
        
        $conditions = [];
        
        if ($category) {
            $conditions[] = "category = :category";
            $params[':category'] = $category;
        }
        
        if ($status !== null) {
            $conditions[] = "status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY is_top DESC, published_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}announcements`";
        $countParams = [];
        
        if ($category) {
            $countSql .= " WHERE category = :category";
            $countParams[':category'] = $category;
        }
        
        if ($status !== null) {
            $countSql .= " WHERE status = :status";
            $countParams[':status'] = $status;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'announcements' => $announcements,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 更新公告
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "UPDATE `{$prefix}announcements` 
                    SET title = :title, content = :content, category = :category, 
                        is_top = :is_top, is_popup = :is_popup, status = :status, 
                        updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':title' => $data['title'],
                ':content' => $data['content'] ?? '',
                ':category' => $data['category'] ?? 'system_update',
                ':is_top' => $data['is_top'] ?? 0,
                ':is_popup' => $data['is_popup'] ?? 0,
                ':status' => $data['status'] ?? 1,
                ':id' => $id
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 发布公告
     *
     * @param int $id
     * @return bool
     */
    public static function publish(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "UPDATE `{$prefix}announcements` 
                    SET status = 1, published_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 删除公告
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "DELETE FROM `{$prefix}announcements` WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 获取公告分类列表
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return [
            'system_update' => '系统更新',
            'activity_notice' => '活动通知',
            'maintenance' => '维护公告'
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
            0 => '草稿',
            1 => '已发布'
        ];
    }

    /**
     * 记录用户阅读公告
     *
     * @param int $userId
     * @param int $announcementId
     * @return bool
     */
    public static function markAsRead(int $userId, int $announcementId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            $sql = "INSERT INTO `{$prefix}user_announcement_reads` (user_id, announcement_id, read_at) 
                    VALUES (:user_id, :announcement_id, NOW())
                    ON DUPLICATE KEY UPDATE read_at = NOW()";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':announcement_id' => $announcementId
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * 检查用户是否已读公告
     *
     * @param int $userId
     * @param int $announcementId
     * @return bool
     */
    public static function isRead(int $userId, int $announcementId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT COUNT(*) FROM `{$prefix}user_announcement_reads` 
                    WHERE user_id = :user_id AND announcement_id = :announcement_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':announcement_id' => $announcementId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * 获取用户未读公告数量
     *
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取所有已发布的公告数量
        $totalSql = "SELECT COUNT(*) FROM `{$prefix}announcements` 
                     WHERE status = 1 AND (published_at IS NULL OR published_at <= NOW())";
        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute();
        $total = (int)$totalStmt->fetchColumn();

        // 获取已读公告数量
        $readSql = "SELECT COUNT(DISTINCT announcement_id) FROM `{$prefix}user_announcement_reads` 
                    WHERE user_id = :user_id";
        $readStmt = $pdo->prepare($readSql);
        $readStmt->execute([':user_id' => $userId]);
        $read = (int)$readStmt->fetchColumn();

        // 未读数量 = 总数 - 已读数量
        return max(0, $total - $read);
    }

    /**
     * 获取用户公告列表（包含已读/未读状态）
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getUserAnnouncements(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        $offset = ($page - 1) * $perPage;

        // 获取所有已发布的公告，并关联读取状态
        $sql = "SELECT a.*, 
                       CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                       r.read_at
                FROM `{$prefix}announcements` a
                LEFT JOIN `{$prefix}user_announcement_reads` r 
                    ON a.id = r.announcement_id AND r.user_id = :user_id
                WHERE a.status = 1 AND (a.published_at IS NULL OR a.published_at <= NOW())
                ORDER BY a.is_top DESC, a.published_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}announcements` 
                     WHERE status = 1 AND (published_at IS NULL OR published_at <= NOW())";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'announcements' => $announcements,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * 获取用户已读公告列表
     *
     * @param int $userId
     * @return array
     */
    public static function getReadAnnouncements(int $userId, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT DISTINCT a.id, a.title, a.published_at, a.is_top, a.is_popup, r.read_at 
                    FROM `{$prefix}announcements` a
                    INNER JOIN `{$prefix}user_announcement_reads` r ON a.id = r.announcement_id AND r.user_id = :user_id
                    ORDER BY r.read_at DESC 
                    LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':limit' => $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取需要弹窗显示的未读公告
     *
     * @param int $userId
     * @return array|null
     */
    public static function getPopupAnnouncement(int $userId): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 获取需要弹窗显示且用户未读的公告
        $sql = "SELECT a.* 
                FROM `{$prefix}announcements` a
                LEFT JOIN `{$prefix}user_announcement_reads` r 
                    ON a.id = r.announcement_id AND r.user_id = :user_id
                WHERE a.status = 1 
                    AND a.is_popup = 1
                    AND (a.published_at IS NULL OR a.published_at <= NOW())
                    AND r.id IS NULL
                ORDER BY a.is_top DESC, a.published_at DESC
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

        return $announcement ?: null;
    }

    /**
     * 记录用户已查看弹窗公告（不标记为已读，只是记录已查看过弹窗）
     *
     * @param int $userId
     * @param int $announcementId
     * @return bool
     */
    public static function markPopupViewed(int $userId, int $announcementId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        try {
            // 使用Session或Cookie记录已查看的弹窗公告ID
            // 这里我们使用一个简单的表来记录，或者可以使用user_announcement_reads表
            // 但为了区分"已读"和"已查看弹窗"，我们可以使用Session
            
            // 如果用户已经标记为已读，就不需要再记录了
            $checkSql = "SELECT COUNT(*) FROM `{$prefix}user_announcement_reads` 
                         WHERE user_id = :user_id AND announcement_id = :announcement_id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                ':user_id' => $userId,
                ':announcement_id' => $announcementId
            ]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return true; // 已经标记为已读
            }

            // 记录到Session中（前端处理）
            return true;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
