<?php

namespace app\models;

use app\services\Database;
use PDO;

class CollaborationProject
{
    /**
     * 创建协作项目
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}collaboration_projects` (
            name, description, creator_id, type, status, is_public, settings
        ) VALUES (
            :name, :description, :creator_id, :type, :status, :is_public, :settings
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':creator_id' => $data['creator_id'],
            ':type' => $data['type'],
            ':status' => $data['status'] ?? 'planning',
            ':is_public' => $data['is_public'] ?? 0,
            ':settings' => json_encode($data['settings'] ?? [])
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取项目
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT p.*, u.username as creator_name, u.avatar as creator_avatar 
                FROM `{$prefix}collaboration_projects` p 
                LEFT JOIN `{$prefix}users` u ON p.creator_id = u.id 
                WHERE p.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            $project['settings'] = json_decode($project['settings'], true);
        }
        
        return $project ?: null;
    }
    
    /**
     * 获取项目列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['creator_id'])) {
            $where[] = "p.creator_id = :creator_id";
            $params[':creator_id'] = $filters['creator_id'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = "p.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['is_public'])) {
            $where[] = "p.is_public = :is_public";
            $params[':is_public'] = $filters['is_public'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = empty($where) ? "1" : implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}collaboration_projects` p WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, u.username as creator_name 
                FROM `{$prefix}collaboration_projects` p 
                LEFT JOIN `{$prefix}users` u ON p.creator_id = u.id 
                WHERE {$whereClause} 
                ORDER BY p.updated_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($projects as &$project) {
            $project['settings'] = json_decode($project['settings'], true);
        }
        
        return [
            'data' => $projects,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户参与的项目
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(DISTINCT p.id) 
                    FROM `{$prefix}collaboration_projects` p 
                    LEFT JOIN `{$prefix}collaboration_members` m ON p.id = m.project_id 
                    WHERE p.creator_id = :user_id OR m.user_id = :user_id";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT DISTINCT p.*, u.username as creator_name,
                       CASE WHEN p.creator_id = :user_id THEN 'owner' ELSE m.role END as user_role
                FROM `{$prefix}collaboration_projects` p 
                LEFT JOIN `{$prefix}users` u ON p.creator_id = u.id 
                LEFT JOIN `{$prefix}collaboration_members` m ON p.id = m.project_id 
                WHERE p.creator_id = :user_id OR m.user_id = :user_id 
                ORDER BY p.updated_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($projects as &$project) {
            $project['settings'] = json_decode($project['settings'], true);
        }
        
        return [
            'data' => $projects,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新项目
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['name', 'description', 'type', 'status', 'is_public'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['settings'])) {
            $fields[] = "settings = :settings";
            $params[':settings'] = json_encode($data['settings']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}collaboration_projects` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除项目
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}collaboration_projects` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 检查用户是否有权限访问项目
     */
    public static function hasPermission(int $projectId, int $userId, string $requiredRole = 'viewer'): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 检查是否是创建者
        $sql = "SELECT creator_id, is_public FROM `{$prefix}collaboration_projects` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            return false;
        }
        
        // 创建者拥有所有权限
        if ($project['creator_id'] == $userId) {
            return true;
        }
        
        // 公开项目至少需要查看权限
        if ($project['is_public'] && $requiredRole === 'viewer') {
            return true;
        }
        
        // 检查成员权限
        $sql = "SELECT role FROM `{$prefix}collaboration_members` 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
        
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            return false;
        }
        
        $roleHierarchy = [
            'viewer' => 1,
            'editor' => 2,
            'admin' => 3,
            'owner' => 4
        ];
        
        return $roleHierarchy[$member['role']] >= $roleHierarchy[$requiredRole];
    }
    
    /**
     * 获取项目统计
     */
    public static function getStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(DISTINCT m.id) as member_count,
                    COUNT(DISTINCT c.id) as content_count,
                    COUNT(DISTINCT CASE WHEN c.type IN ('novel', 'anime', 'music') THEN c.id END) as creative_content_count,
                    COUNT(DISTINCT CASE WHEN c.type IN ('comment', 'annotation') THEN c.id END) as interaction_count
                FROM `{$prefix}collaboration_projects` p 
                LEFT JOIN `{$prefix}collaboration_members` m ON p.id = m.project_id 
                LEFT JOIN `{$prefix}collaboration_contents` c ON p.id = c.project_id 
                WHERE p.id = :project_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}