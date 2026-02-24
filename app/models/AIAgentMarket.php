<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIAgent
{
    /**
     * 创建AI智能体
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}ai_agents` (
            creator_id, name, description, avatar, category, role, abilities, 
            prompt_template, price_type, price, rental_daily_price, status
        ) VALUES (
            :creator_id, :name, :description, :avatar, :category, :role, 
            :abilities, :prompt_template, :price_type, :price, :rental_daily_price, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':creator_id' => $data['creator_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':avatar' => $data['avatar'] ?? null,
            ':category' => $data['category'] ?? null,
            ':role' => $data['role'],
            ':abilities' => json_encode($data['abilities'] ?? []),
            ':prompt_template' => $data['prompt_template'],
            ':price_type' => $data['price_type'] ?? 'free',
            ':price' => $data['price'] ?? 0.00,
            ':rental_daily_price' => $data['rental_daily_price'] ?? 0.00,
            ':status' => $data['status'] ?? 1
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取智能体
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT a.*, u.username as creator_name, u.avatar as creator_avatar 
                FROM `{$prefix}ai_agents` a 
                LEFT JOIN `{$prefix}users` u ON a.creator_id = u.id 
                WHERE a.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($agent) {
            $agent['abilities'] = json_decode($agent['abilities'], true);
        }
        
        return $agent ?: null;
    }
    
    /**
     * 获取智能体列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["a.status = 1"];
        $params = [];
        
        if (!empty($filters['category'])) {
            $where[] = "a.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['price_type'])) {
            $where[] = "a.price_type = :price_type";
            $params[':price_type'] = $filters['price_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(a.name LIKE :search OR a.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agents` a WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, u.username as creator_name 
                FROM `{$prefix}ai_agents` a 
                LEFT JOIN `{$prefix}users` u ON a.creator_id = u.id 
                WHERE {$whereClause} 
                ORDER BY a.is_featured DESC, a.rating DESC, a.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($agents as &$agent) {
            $agent['abilities'] = json_decode($agent['abilities'], true);
        }
        
        return [
            'data' => $agents,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新智能体
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['name', 'description', 'avatar', 'category', 'role', 'prompt_template', 'price_type', 'price', 'rental_daily_price', 'status', 'is_featured'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['abilities'])) {
            $fields[] = "abilities = :abilities";
            $params[':abilities'] = json_encode($data['abilities']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}ai_agents` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除智能体
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}ai_agents` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加使用次数
     */
    public static function incrementUsage(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}ai_agents` SET usage_count = usage_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 更新评分
     */
    public static function updateRating(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}ai_agents` a 
                SET a.rating = (
                    SELECT COALESCE(AVG(rating), 0) 
                    FROM `{$prefix}ai_agent_reviews` r 
                    WHERE r.agent_id = a.id
                ),
                a.rating_count = (
                    SELECT COUNT(*) 
                    FROM `{$prefix}ai_agent_reviews` r 
                    WHERE r.agent_id = a.id
                )
                WHERE a.id = :id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取用户创建的智能体
     */
    public static function getByCreator(int $creatorId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agents` WHERE creator_id = :creator_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':creator_id' => $creatorId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT * FROM `{$prefix}ai_agents` 
                WHERE creator_id = :creator_id 
                ORDER BY created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':creator_id', $creatorId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($agents as &$agent) {
            $agent['abilities'] = json_decode($agent['abilities'], true);
        }
        
        return [
            'data' => $agents,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取热门智能体
     */
    public static function getPopular(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT a.*, u.username as creator_name 
                FROM `{$prefix}ai_agents` a 
                LEFT JOIN `{$prefix}users` u ON a.creator_id = u.id 
                WHERE a.status = 1 
                ORDER BY a.usage_count DESC, a.rating DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($agents as &$agent) {
            $agent['abilities'] = json_decode($agent['abilities'], true);
        }
        
        return $agents;
    }
    
    /**
     * 获取推荐智能体
     */
    public static function getFeatured(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT a.*, u.username as creator_name 
                FROM `{$prefix}ai_agents` a 
                LEFT JOIN `{$prefix}users` u ON a.creator_id = u.id 
                WHERE a.status = 1 AND a.is_featured = 1 
                ORDER BY a.rating DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($agents as &$agent) {
            $agent['abilities'] = json_decode($agent['abilities'], true);
        }
        
        return $agents;
    }
}