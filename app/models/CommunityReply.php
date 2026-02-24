<?php

namespace app\models;

use app\services\Database;
use PDO;

class CommunityReply
{
    /**
     * 创建社区回复
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}community_replies` (
            post_id, user_id, parent_id, content, status
        ) VALUES (
            :post_id, :user_id, :parent_id, :content, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':post_id' => $data['post_id'],
            ':user_id' => $data['user_id'],
            ':parent_id' => $data['parent_id'] ?? null,
            ':content' => $data['content'],
            ':status' => $data['status'] ?? 'published'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取回复
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar,
                       p.title as post_title, p.user_id as post_author_id
                FROM `{$prefix}community_replies` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                LEFT JOIN `{$prefix}creation_community` p ON r.post_id = p.id 
                WHERE r.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * 获取帖子的回复列表
     */
    public static function getByPost(int $postId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}community_replies` WHERE post_id = :post_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':post_id' => $postId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}community_replies` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                WHERE r.post_id = :post_id AND r.status = 'published'
                ORDER BY r.is_best_answer DESC, r.created_at ASC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':post_id', $postId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $replies,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的回复列表
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}community_replies` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT r.*, p.title as post_title, p.type as post_type
                FROM `{$prefix}community_replies` r 
                LEFT JOIN `{$prefix}creation_community` p ON r.post_id = p.id 
                WHERE r.user_id = :user_id 
                ORDER BY r.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $replies,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取回复的子回复
     */
    public static function getChildren(int $parentId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}community_replies` WHERE parent_id = :parent_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':parent_id' => $parentId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}community_replies` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                WHERE r.parent_id = :parent_id AND r.status = 'published'
                ORDER BY r.created_at ASC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':parent_id', $parentId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $replies,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新回复
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['content', 'status', 'is_best_answer'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}community_replies` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除回复
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}community_replies` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 设置为最佳答案
     */
    public static function setBestAnswer(int $id, int $postId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 先清除该帖子的所有最佳答案标记
        $clearSql = "UPDATE `{$prefix}community_replies` 
                     SET is_best_answer = 0 
                     WHERE post_id = :post_id";
        
        $clearStmt = $pdo->prepare($clearSql);
        $clearStmt->execute([':post_id' => $postId]);
        
        // 设置新的最佳答案
        $sql = "UPDATE `{$prefix}community_replies` 
                SET is_best_answer = 1 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加点赞数
     */
    public static function incrementLike(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}community_replies` SET like_count = like_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取回复统计
     */
    public static function getStats(int $postId = null, int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($postId) {
            $where[] = "post_id = :post_id";
            $params[':post_id'] = $postId;
        }
        
        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(*) as total_replies,
                    SUM(CASE WHEN is_best_answer = 1 THEN 1 ELSE 0 END) as best_answer_count,
                    SUM(like_count) as total_likes,
                    AVG(like_count) as avg_likes,
                    COUNT(DISTINCT post_id) as unique_posts,
                    COUNT(DISTINCT user_id) as unique_users
                FROM `{$prefix}community_replies` 
                WHERE {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取活跃回复者
     */
    public static function getActiveRepliers(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT u.id, u.username, u.avatar,
                       COUNT(r.id) as reply_count,
                       SUM(r.like_count) as total_likes,
                       AVG(r.like_count) as avg_likes,
                       MAX(r.created_at) as last_reply
                FROM `{$prefix}community_replies` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                WHERE r.status = 'published'
                GROUP BY u.id 
                ORDER BY reply_count DESC, total_likes DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}