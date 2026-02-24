<?php

namespace app\models;

use app\services\Database;
use PDO;

class UserPreference
{
    /**
     * 创建或更新用户偏好
     */
    public static function set(int $userId, string $category, array $preferenceData, float $weight = 1.00): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}user_preferences` (
            user_id, category, preference_data, weight
        ) VALUES (
            :user_id, :category, :preference_data, :weight
        ) ON DUPLICATE KEY UPDATE 
            preference_data = :preference_data2,
            weight = :weight2,
            updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':category' => $category,
            ':preference_data' => json_encode($preferenceData),
            ':weight' => $weight,
            ':preference_data2' => json_encode($preferenceData),
            ':weight2' => $weight
        ]);
    }
    
    /**
     * 获取用户偏好
     */
    public static function get(int $userId, string $category): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}user_preferences` 
                WHERE user_id = :user_id AND category = :category";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':category' => $category
        ]);
        
        $preference = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($preference) {
            $preference['preference_data'] = json_decode($preference['preference_data'], true);
        }
        
        return $preference ?: null;
    }
    
    /**
     * 获取用户所有偏好
     */
    public static function getAll(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT * FROM `{$prefix}user_preferences` 
                WHERE user_id = :user_id 
                ORDER BY weight DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($preferences as &$preference) {
            $preference['preference_data'] = json_decode($preference['preference_data'], true);
        }
        
        return $preferences;
    }
    
    /**
     * 更新偏好权重
     */
    public static function updateWeight(int $userId, string $category, float $weight): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}user_preferences` 
                SET weight = :weight, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = :user_id AND category = :category";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':weight' => $weight,
            ':user_id' => $userId,
            ':category' => $category
        ]);
    }
    
    /**
     * 删除用户偏好
     */
    public static function delete(int $userId, string $category): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}user_preferences` 
                WHERE user_id = :user_id AND category = :category";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':category' => $category
        ]);
    }
    
    /**
     * 基于用户行为更新偏好
     */
    public static function updateFromBehavior(int $userId, string $category, string $action, array $itemData): bool
    {
        $preference = self::get($userId, $category);
        
        if (!$preference) {
            $preferenceData = [];
            $weight = 1.00;
        } else {
            $preferenceData = $preference['preference_data'];
            $weight = $preference['weight'];
        }
        
        // 根据行为类型更新偏好数据
        switch ($action) {
            case 'view':
                $weight += 0.1;
                break;
            case 'like':
                $weight += 0.5;
                break;
            case 'share':
                $weight += 0.3;
                break;
            case 'purchase':
                $weight += 1.0;
                break;
            case 'dislike':
                $weight -= 0.3;
                break;
        }
        
        // 更新具体偏好项
        foreach ($itemData as $key => $value) {
            if (!isset($preferenceData[$key])) {
                $preferenceData[$key] = [];
            }
            
            if (!isset($preferenceData[$key][$value])) {
                $preferenceData[$key][$value] = 0;
            }
            
            $preferenceData[$key][$value] += 1;
        }
        
        // 限制权重范围
        $weight = max(0.1, min(10.0, $weight));
        
        return self::set($userId, $category, $preferenceData, $weight);
    }
    
    /**
     * 获取相似用户
     */
    public static function getSimilarUsers(int $userId, int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT u.id, u.username, u.avatar,
                       (SELECT COUNT(*) FROM `{$prefix}user_preferences` p1 
                        WHERE p1.user_id = u.id AND p1.category = 'content') as pref_count
                FROM `{$prefix}users` u 
                WHERE u.id != :user_id 
                AND EXISTS (
                    SELECT 1 FROM `{$prefix}user_preferences` p2 
                    WHERE p2.user_id = u.id
                )
                ORDER BY pref_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取偏好统计
     */
    public static function getStats(int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $userId ? "WHERE user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_preferences,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT category) as unique_categories,
                    AVG(weight) as avg_weight,
                    MAX(weight) as max_weight,
                    MIN(weight) as min_weight
                FROM `{$prefix}user_preferences` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取热门偏好类别
     */
    public static function getPopularCategories(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    category,
                    COUNT(*) as user_count,
                    AVG(weight) as avg_weight
                FROM `{$prefix}user_preferences` 
                GROUP BY category 
                ORDER BY user_count DESC, avg_weight DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}