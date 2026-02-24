<?php

namespace app\models;

use app\services\Database;
use PDO;

class AIAgentPurchase
{
    /**
     * 创建购买记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}ai_agent_purchases` (
            user_id, agent_id, type, price, rental_days, expires_at
        ) VALUES (
            :user_id, :agent_id, :type, :price, :rental_days, :expires_at
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':agent_id' => $data['agent_id'],
            ':type' => $data['type'],
            ':price' => $data['price'],
            ':rental_days' => $data['rental_days'] ?? null,
            ':expires_at' => $data['expires_at'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 检查用户是否已购买智能体
     */
    public static function hasPurchased(int $userId, int $agentId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_purchases` 
                WHERE user_id = :user_id AND agent_id = :agent_id 
                AND (type = 'purchase' OR (type = 'rental' AND expires_at > NOW()))";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':agent_id' => $agentId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取用户的购买记录
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_purchases` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT p.*, a.name as agent_name, a.avatar as agent_avatar 
                FROM `{$prefix}ai_agent_purchases` p 
                LEFT JOIN `{$prefix}ai_agents` a ON p.agent_id = a.id 
                WHERE p.user_id = :user_id 
                ORDER BY p.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $purchases,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取智能体的销售记录
     */
    public static function getByAgent(int $agentId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}ai_agent_purchases` WHERE agent_id = :agent_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':agent_id' => $agentId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT p.*, u.username as buyer_name 
                FROM `{$prefix}ai_agent_purchases` p 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE p.agent_id = :agent_id 
                ORDER BY p.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':agent_id', $agentId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $purchases,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取即将到期的租赁
     */
    public static function getExpiringRentals(int $days = 3): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT p.*, a.name as agent_name, u.username as user_name, u.email as user_email 
                FROM `{$prefix}ai_agent_purchases` p 
                LEFT JOIN `{$prefix}ai_agents` a ON p.agent_id = a.id 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE p.type = 'rental' 
                AND p.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY) 
                ORDER BY p.expires_at ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取销售统计
     */
    public static function getSalesStats(int $agentId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = "";
        $params = [];
        
        if ($agentId) {
            $whereClause = "WHERE agent_id = :agent_id";
            $params[':agent_id'] = $agentId;
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_sales,
                    SUM(CASE WHEN type = 'purchase' THEN 1 ELSE 0 END) as purchase_count,
                    SUM(CASE WHEN type = 'rental' THEN 1 ELSE 0 END) as rental_count,
                    SUM(price) as total_revenue,
                    AVG(price) as avg_price
                FROM `{$prefix}ai_agent_purchases` 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}