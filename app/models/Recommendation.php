<?php

namespace app\models;

use app\services\Database;
use PDO;

class Recommendation
{
    /**
     * 创建推荐记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}recommendations` (
            user_id, item_type, item_id, score, reason, algorithm
        ) VALUES (
            :user_id, :item_type, :item_id, :score, :reason, :algorithm
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':item_type' => $data['item_type'],
            ':item_id' => $data['item_id'],
            ':score' => $data['score'],
            ':reason' => $data['reason'] ?? null,
            ':algorithm' => $data['algorithm'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 批量创建推荐记录
     */
    public static function createBatch(int $userId, array $recommendations): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}recommendations` (
            user_id, item_type, item_id, score, reason, algorithm
        ) VALUES ";
        
        $values = [];
        $params = [':user_id' => $userId];
        
        foreach ($recommendations as $index => $rec) {
            $values[] = "(:user_id, :item_type_{$index}, :item_id_{$index}, :score_{$index}, :reason_{$index}, :algorithm_{$index})";
            $params[":item_type_{$index}"] = $rec['item_type'];
            $params[":item_id_{$index}"] = $rec['item_id'];
            $params[":score_{$index}"] = $rec['score'];
            $params[":reason_{$index}"] = $rec['reason'] ?? null;
            $params[":algorithm_{$index}"] = $rec['algorithm'] ?? null;
        }
        
        $sql .= implode(', ', $values);
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * 获取用户推荐列表
     */
    public static function getByUser(int $userId, string $itemType = null, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["r.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if ($itemType) {
            $where[] = "r.item_type = :item_type";
            $params[':item_type'] = $itemType;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT r.* 
                FROM `{$prefix}recommendations` r 
                WHERE {$whereClause} 
                ORDER BY r.score DESC, r.created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取推荐详情
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT r.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}recommendations` r 
                LEFT JOIN `{$prefix}users` u ON r.user_id = u.id 
                WHERE r.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * 标记推荐为已点击
     */
    public static function markClicked(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}recommendations` 
                SET is_clicked = 1, clicked_at = CURRENT_TIMESTAMP 
                WHERE id = :id AND is_clicked = 0";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 标记推荐为已喜欢
     */
    public static function markLiked(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}recommendations` 
                SET is_liked = 1 
                WHERE id = :id AND is_liked = 0";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 更新推荐分数
     */
    public static function updateScore(int $id, float $newScore): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}recommendations` 
                SET score = :score 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':score' => $newScore,
            ':id' => $id
        ]);
    }
    
    /**
     * 删除推荐记录
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}recommendations` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 清理过期推荐
     */
    public static function cleanup(int $daysOld = 30): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}recommendations` 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * 获取推荐统计
     */
    public static function getStats(int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $userId ? "WHERE user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_recommendations,
                    SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) as clicked_count,
                    SUM(CASE WHEN is_liked = 1 THEN 1 ELSE 0 END) as liked_count,
                    AVG(score) as avg_score,
                    MAX(score) as max_score,
                    MIN(score) as min_score,
                    COUNT(DISTINCT item_type) as unique_types,
                    COUNT(DISTINCT algorithm) as unique_algorithms
                FROM `{$prefix}recommendations` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取推荐效果统计
     */
    public static function getPerformanceStats(string $algorithm = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $algorithm ? "WHERE algorithm = :algorithm" : "";
        $params = $algorithm ? [':algorithm' => $algorithm] : [];
        
        $sql = "SELECT 
                    algorithm,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) as clicked_count,
                    SUM(CASE WHEN is_liked = 1 THEN 1 ELSE 0 END) as liked_count,
                    ROUND(SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as click_rate,
                    ROUND(SUM(CASE WHEN is_liked = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as like_rate,
                    AVG(score) as avg_score
                FROM `{$prefix}recommendations` 
                {$whereClause}
                GROUP BY algorithm 
                ORDER BY click_rate DESC, like_rate DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取热门推荐项目
     */
    public static function getPopularItems(string $itemType = null, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($itemType) {
            $where[] = "item_type = :item_type";
            $params[':item_type'] = $itemType;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT 
                    item_type,
                    item_id,
                    COUNT(*) as recommendation_count,
                    SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) as click_count,
                    SUM(CASE WHEN is_liked = 1 THEN 1 ELSE 0 END) as like_count,
                    AVG(score) as avg_score,
                    ROUND(SUM(CASE WHEN is_clicked = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as click_rate
                FROM `{$prefix}recommendations` 
                WHERE {$whereClause} 
                GROUP BY item_type, item_id 
                ORDER BY recommendation_count DESC, click_rate DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 基于协同过滤生成推荐
     */
    public static function generateCollaborativeRecommendations(int $userId, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 获取相似用户喜欢的项目
        $sql = "SELECT r.item_type, r.item_id, COUNT(*) as like_count
                FROM `{$prefix}recommendations` r 
                WHERE r.user_id IN (
                    SELECT DISTINCT r2.user_id 
                    FROM `{$prefix}recommendations` r2 
                    WHERE r2.user_id != :user_id 
                    AND r2.item_id IN (
                        SELECT item_id FROM `{$prefix}recommendations` 
                        WHERE user_id = :user_id AND is_liked = 1
                    )
                    LIMIT 50
                )
                AND r.is_liked = 1
                AND r.item_id NOT IN (
                    SELECT item_id FROM `{$prefix}recommendations` 
                    WHERE user_id = :user_id
                )
                GROUP BY r.item_type, r.item_id 
                ORDER BY like_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 基于内容过滤生成推荐
     */
    public static function generateContentBasedRecommendations(int $userId, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 获取用户偏好并生成推荐
        $sql = "SELECT 
                    'novel' as item_type,
                    n.id as item_id,
                    (CASE 
                        WHEN p.preference_data LIKE CONCAT('%', n.category, '%') THEN 0.8
                        WHEN p.preference_data LIKE CONCAT('%', n.tags, '%') THEN 0.6
                        ELSE 0.3
                    END) as score,
                    '基于内容偏好' as reason,
                    'content_based' as algorithm
                FROM `{$prefix}novels` n 
                CROSS JOIN `{$prefix}user_preferences` p 
                WHERE p.user_id = :user_id 
                AND p.category = 'content'
                AND n.id NOT IN (
                    SELECT item_id FROM `{$prefix}recommendations` 
                    WHERE user_id = :user_id AND item_type = 'novel'
                )
                ORDER BY score DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}