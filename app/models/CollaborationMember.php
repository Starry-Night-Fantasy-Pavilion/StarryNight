<?php

namespace app\models;

use app\services\Database;
use PDO;

class CollaborationMember
{
    /**
     * 添加成员
     */
    public static function add(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}collaboration_members` (
            project_id, user_id, role, permissions
        ) VALUES (
            :project_id, :user_id, :role, :permissions
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $data['project_id'],
            ':user_id' => $data['user_id'],
            ':role' => $data['role'],
            ':permissions' => json_encode($data['permissions'] ?? [])
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 获取项目成员列表
     */
    public static function getByProject(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT m.*, u.username, u.avatar, u.email,
                       CASE WHEN p.creator_id = u.id THEN 1 ELSE 0 END as is_creator
                FROM `{$prefix}collaboration_members` m 
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id 
                LEFT JOIN `{$prefix}collaboration_projects` p ON m.project_id = p.id 
                WHERE m.project_id = :project_id 
                ORDER BY m.joined_at ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($members as &$member) {
            $member['permissions'] = json_decode($member['permissions'], true);
        }
        
        return $members;
    }
    
    /**
     * 获取用户参与的项目
     */
    public static function getByUser(int $userId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT m.*, p.name as project_name, p.type as project_type, p.status as project_status
                FROM `{$prefix}collaboration_members` m 
                LEFT JOIN `{$prefix}collaboration_projects` p ON m.project_id = p.id 
                WHERE m.user_id = :user_id 
                ORDER BY m.joined_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($memberships as &$membership) {
            $membership['permissions'] = json_decode($membership['permissions'], true);
        }
        
        return $memberships;
    }
    
    /**
     * 检查用户是否是项目成员
     */
    public static function isMember(int $projectId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT COUNT(*) FROM `{$prefix}collaboration_members` 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * 获取用户在项目中的角色
     */
    public static function getRole(int $projectId, int $userId): ?string
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 首先检查是否是创建者
        $sql = "SELECT creator_id FROM `{$prefix}collaboration_projects` WHERE id = :project_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project && $project['creator_id'] == $userId) {
            return 'owner';
        }
        
        // 检查成员角色
        $sql = "SELECT role FROM `{$prefix}collaboration_members` 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
        
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $member ? $member['role'] : null;
    }
    
    /**
     * 更新成员角色
     */
    public static function updateRole(int $projectId, int $userId, string $role): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}collaboration_members` 
                SET role = :role 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':role' => $role,
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 更新成员权限
     */
    public static function updatePermissions(int $projectId, int $userId, array $permissions): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}collaboration_members` 
                SET permissions = :permissions 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':permissions' => json_encode($permissions),
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 移除成员
     */
    public static function remove(int $projectId, int $userId): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}collaboration_members` 
                WHERE project_id = :project_id AND user_id = :user_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * 检查权限
     */
    public static function hasPermission(int $projectId, int $userId, string $permission): bool
    {
        $role = self::getRole($projectId, $userId);
        
        if (!$role) {
            return false;
        }
        
        // 所有者拥有所有权限
        if ($role === 'owner') {
            return true;
        }
        
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT permissions FROM `{$prefix}collaboration_members` 
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
        
        $permissions = json_decode($member['permissions'], true);
        
        return in_array($permission, $permissions);
    }
    
    /**
     * 获取成员统计
     */
    public static function getStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_members,
                    SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) as owners,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN role = 'editor' THEN 1 ELSE 0 END) as editors,
                    SUM(CASE WHEN role = 'viewer' THEN 1 ELSE 0 END) as viewers
                FROM `{$prefix}collaboration_members` 
                WHERE project_id = :project_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}