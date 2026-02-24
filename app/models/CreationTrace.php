<?php

namespace app\models;

use app\services\Database;
use PDO;

class CreationTrace
{
    /**
     * 创建溯源记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}creation_traces` (
            copyright_id, user_id, action, ai_model, prompt, parameters, input_data, output_data
        ) VALUES (
            :copyright_id, :user_id, :action, :ai_model, :prompt, :parameters, :input_data, :output_data
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':copyright_id' => $data['copyright_id'],
            ':user_id' => $data['user_id'],
            ':action' => $data['action'],
            ':ai_model' => $data['ai_model'] ?? null,
            ':prompt' => $data['prompt'] ?? null,
            ':parameters' => json_encode($data['parameters'] ?? []),
            ':input_data' => $data['input_data'] ?? null,
            ':output_data' => $data['output_data'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取溯源记录
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT ct.*, u.username as user_name, u.avatar as user_avatar,
                       cr.title as work_title, cr.content_type as work_type
                FROM `{$prefix}creation_traces` ct 
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON ct.copyright_id = cr.id 
                WHERE ct.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $trace = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trace) {
            $trace['parameters'] = json_decode($trace['parameters'], true);
        }
        
        return $trace ?: null;
    }
    
    /**
     * 获取版权的溯源记录
     */
    public static function getByCopyright(int $copyrightId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_traces` WHERE copyright_id = :copyright_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':copyright_id' => $copyrightId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT ct.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}creation_traces` ct 
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id 
                WHERE ct.copyright_id = :copyright_id 
                ORDER BY ct.timestamp ASC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':copyright_id', $copyrightId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $traces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($traces as &$trace) {
            $trace['parameters'] = json_decode($trace['parameters'], true);
        }
        
        return [
            'data' => $traces,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的溯源记录
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["ct.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($filters['action'])) {
            $where[] = "ct.action = :action";
            $params[':action'] = $filters['action'];
        }
        
        if (!empty($filters['ai_model'])) {
            $where[] = "ct.ai_model = :ai_model";
            $params[':ai_model'] = $filters['ai_model'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(ct.timestamp) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(ct.timestamp) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_traces` ct WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT ct.*, cr.title as work_title, cr.content_type as work_type
                FROM `{$prefix}creation_traces` ct 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON ct.copyright_id = cr.id 
                WHERE {$whereClause} 
                ORDER BY ct.timestamp DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $traces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($traces as &$trace) {
            $trace['parameters'] = json_decode($trace['parameters'], true);
        }
        
        return [
            'data' => $traces,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取溯源记录列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['copyright_id'])) {
            $where[] = "ct.copyright_id = :copyright_id";
            $params[':copyright_id'] = $filters['copyright_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "ct.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "ct.action = :action";
            $params[':action'] = $filters['action'];
        }
        
        if (!empty($filters['ai_model'])) {
            $where[] = "ct.ai_model = :ai_model";
            $params[':ai_model'] = $filters['ai_model'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(ct.timestamp) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(ct.timestamp) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_traces` ct WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT ct.*, u.username as user_name, u.avatar as user_avatar,
                       cr.title as work_title, cr.content_type as work_type
                FROM `{$prefix}creation_traces` ct 
                LEFT JOIN `{$prefix}users` u ON ct.user_id = u.id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON ct.copyright_id = cr.id 
                WHERE {$whereClause} 
                ORDER BY ct.timestamp DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $traces = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($traces as &$trace) {
            $trace['parameters'] = json_decode($trace['parameters'], true);
        }
        
        return [
            'data' => $traces,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 删除溯源记录
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}creation_traces` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取溯源统计
     */
    public static function getStats(int $copyrightId = null, int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if ($copyrightId) {
            $where[] = "copyright_id = :copyright_id";
            $params[':copyright_id'] = $copyrightId;
        }
        
        if ($userId) {
            $where[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        $sql = "SELECT 
                    COUNT(*) as total_traces,
                    COUNT(DISTINCT copyright_id) as unique_works,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ai_model) as unique_models,
                    COUNT(DISTINCT action) as unique_actions
                FROM `{$prefix}creation_traces` 
                WHERE {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取AI模型使用统计
     */
    public static function getModelStats(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    ai_model,
                    COUNT(*) as usage_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT copyright_id) as unique_works
                FROM `{$prefix}creation_traces` 
                WHERE ai_model IS NOT NULL 
                GROUP BY ai_model 
                ORDER BY usage_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取操作类型统计
     */
    public static function getActionStats(int $copyrightId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $copyrightId ? "WHERE copyright_id = :copyright_id" : "";
        $params = $copyrightId ? [':copyright_id' => $copyrightId] : [];
        
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM `{$prefix}creation_traces` 
                {$whereClause}
                GROUP BY action 
                ORDER BY count DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}