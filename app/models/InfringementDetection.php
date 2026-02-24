<?php

namespace app\models;

use app\services\Database;
use PDO;

class InfringementDetection
{
    /**
     * 创建侵权检测记录
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}infringement_detections` (
            copyright_id, detected_url, similarity_score, detection_method, status, admin_notes
        ) VALUES (
            :copyright_id, :detected_url, :similarity_score, :detection_method, :status, :admin_notes
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':copyright_id' => $data['copyright_id'],
            ':detected_url' => $data['detected_url'],
            ':similarity_score' => $data['similarity_score'],
            ':detection_method' => $data['detection_method'],
            ':status' => $data['status'] ?? 'detected',
            ':admin_notes' => $data['admin_notes'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取侵权检测记录
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT id.*, cr.title as work_title, cr.content_type as work_type,
                       u.username as owner_name, u.avatar as owner_avatar
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE id.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * 获取版权的侵权检测记录
     */
    public static function getByCopyright(int $copyrightId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}infringement_detections` WHERE copyright_id = :copyright_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':copyright_id' => $copyrightId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT * FROM `{$prefix}infringement_detections` 
                WHERE copyright_id = :copyright_id 
                ORDER BY detected_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':copyright_id', $copyrightId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $detections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $detections,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的侵权检测记录
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["cr.user_id = :user_id"];
        $params = [':user_id' => $userId];
        
        if (!empty($filters['status'])) {
            $where[] = "id.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['similarity_min'])) {
            $where[] = "id.similarity_score >= :similarity_min";
            $params[':similarity_min'] = $filters['similarity_min'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(id.detected_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(id.detected_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}infringement_detections` id 
                    LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                    WHERE {$whereClause}";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id.*, cr.title as work_title, cr.content_type as work_type
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                WHERE {$whereClause} 
                ORDER BY id.detected_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $detections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $detections,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取侵权检测记录列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['copyright_id'])) {
            $where[] = "id.copyright_id = :copyright_id";
            $params[':copyright_id'] = $filters['copyright_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "cr.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "id.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['similarity_min'])) {
            $where[] = "id.similarity_score >= :similarity_min";
            $params[':similarity_min'] = $filters['similarity_min'];
        }
        
        if (!empty($filters['detection_method'])) {
            $where[] = "id.detection_method = :detection_method";
            $params[':detection_method'] = $filters['detection_method'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(id.detected_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(id.detected_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}infringement_detections` id 
                    LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                    WHERE {$whereClause}";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id.*, cr.title as work_title, cr.content_type as work_type,
                       u.username as owner_name, u.avatar as owner_avatar
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE {$whereClause} 
                ORDER BY id.detected_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $detections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $detections,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新侵权检测记录
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['status', 'admin_notes', 'resolved_at'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}infringement_detections` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除侵权检测记录
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}infringement_detections` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取待处理的侵权检测
     */
    public static function getPending(int $limit = 50): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT id.*, cr.title as work_title, cr.content_type as work_type,
                       u.username as owner_name, u.email as owner_email
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE id.status IN ('detected', 'reviewing') 
                ORDER BY id.similarity_score DESC, id.detected_at ASC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取高相似度侵权检测
     */
    public static function getHighSimilarity(float $threshold = 0.8, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT id.*, cr.title as work_title, cr.content_type as work_type,
                       u.username as owner_name
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                LEFT JOIN `{$prefix}users` u ON cr.user_id = u.id 
                WHERE id.similarity_score >= :threshold 
                AND id.status NOT IN ('false_positive', 'resolved') 
                ORDER BY id.similarity_score DESC, id.detected_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':threshold', $threshold);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取侵权检测统计
     */
    public static function getStats(int $userId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $whereClause = $userId ? "WHERE cr.user_id = :user_id" : "";
        $params = $userId ? [':user_id' => $userId] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total_detections,
                    SUM(CASE WHEN status = 'detected' THEN 1 ELSE 0 END) as detected_count,
                    SUM(CASE WHEN status = 'reviewing' THEN 1 ELSE 0 END) as reviewing_count,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN status = 'false_positive' THEN 1 ELSE 0 END) as false_positive_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                    AVG(similarity_score) as avg_similarity,
                    MAX(similarity_score) as max_similarity
                FROM `{$prefix}infringement_detections` id 
                LEFT JOIN `{$prefix}copyright_registrations` cr ON id.copyright_id = cr.id 
                {$whereClause}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取检测方法统计
     */
    public static function getMethodStats(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    detection_method,
                    COUNT(*) as detection_count,
                    AVG(similarity_score) as avg_similarity,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count
                FROM `{$prefix}infringement_detections` 
                GROUP BY detection_method 
                ORDER BY detection_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}