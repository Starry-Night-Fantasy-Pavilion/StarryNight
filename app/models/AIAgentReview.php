<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIAgentReview
{
    /**
     * 创建评价
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}ai_agent_reviews` (
            agent_id, user_id, rating, comment
        ) VALUES (
            :agent_id, :user_id, :rating, :comment
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':agent_id' => $data['agent_id'],
            ':user_id' => $data['user_id'],
            ':rating' => $data['rating'],
            ':comment' => $data['comment'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 检查用户是否已评价
     */
    public static function hasReviewed(int $userId, int $agentId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_reviews` 
                WHERE user_id = :user_id AND agent_id = :agent_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':agent_id' => $agentId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取智能体的评价列表
     */
    public static function getByAgent(int $agentId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_reviews` WHERE agent_id = :agent_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':agent_id' => $agentId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar 
                FROM `{$prefix}ai_agent_reviews` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                WHERE r.agent_id = :agent_id 
                ORDER BY r.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':agent_id', $agentId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $reviews,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的评价记录
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_reviews` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT r.*, a.name as agent_name, a.avatar as agent_avatar 
                FROM `{$prefix}ai_agent_reviews` r 
                LEFT JOIN `{$prefix}ai_agents` a ON r.agent_id = a.id 
                WHERE r.user_id = :user_id 
                ORDER BY r.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $reviews,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新评价
     */
    public static function update(int $userId, int $agentId, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [
            ':user_id' => $userId,
            ':agent_id' => $agentId
        ];
        
        if (isset($data['rating'])) {
            $fields[] = "rating = :rating";
            $params[':rating'] = $data['rating'];
        }
        
        if (isset($data['comment'])) {
            $fields[] = "comment = :comment";
            $params[':comment'] = $data['comment'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE `{$prefix}ai_agent_reviews` SET " . implode(', ', $fields) . 
                " WHERE user_id = :user_id AND agent_id = :agent_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 删除评价
     */
    public static function delete(int $userId, int $agentId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}ai_agent_reviews` 
                WHERE user_id = :user_id AND agent_id = :agent_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':agent_id' => $agentId
        ]);
    }
    
    /**
     * 获取评价统计
     */
    public static function getRatingStats(int $agentId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM `{$prefix}ai_agent_reviews` 
                WHERE agent_id = :agent_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':agent_id' => $agentId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取最新评价
     */
    public static function getLatest(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar,
                       a.name as agent_name, a.avatar as agent_avatar
                FROM `{$prefix}ai_agent_reviews` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                LEFT JOIN `{$prefix}ai_agents` a ON r.agent_id = a.id 
                ORDER BY r.created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}