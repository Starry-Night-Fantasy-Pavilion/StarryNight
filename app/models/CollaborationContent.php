<?php

namespace app\models;

use app\services\Database;
use PDO;

class CollaborationContent
{
    /**
     * 创建内容
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}collaboration_contents` (
            project_id, creator_id, type, title, content, file_url, parent_id, version, status
        ) VALUES (
            :project_id, :creator_id, :type, :title, :content, :file_url, :parent_id, :version, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $data['project_id'],
            ':creator_id' => $data['creator_id'],
            ':type' => $data['type'],
            ':title' => $data['title'] ?? null,
            ':content' => $data['content'] ?? null,
            ':file_url' => $data['file_url'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':version' => $data['version'] ?? 1,
            ':status' => $data['status'] ?? 'draft'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取内容
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.*, u.username as creator_name, u.avatar as creator_avatar,
                       p.name as project_name
                FROM `{$prefix}collaboration_contents` c 
                LEFT JOIN `{$prefix}users` u ON c.creator_id = u.id 
                LEFT JOIN `{$prefix}collaboration_projects` p ON c.project_id = p.id 
                WHERE c.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * 获取项目内容列表
     */
    public static function getByProject(int $projectId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["c.project_id = :project_id"];
        $params = [':project_id' => $projectId];
        
        if (!empty($filters['type'])) {
            $where[] = "c.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['creator_id'])) {
            $where[] = "c.creator_id = :creator_id";
            $params[':creator_id'] = $filters['creator_id'];
        }
        
        if (!empty($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null') {
                $where[] = "c.parent_id IS NULL";
            } else {
                $where[] = "c.parent_id = :parent_id";
                $params[':parent_id'] = $filters['parent_id'];
            }
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.title LIKE :search OR c.content LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}collaboration_contents` c WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, u.username as creator_name, u.avatar as creator_avatar
                FROM `{$prefix}collaboration_contents` c 
                LEFT JOIN `{$prefix}users` u ON c.creator_id = u.id 
                WHERE {$whereClause} 
                ORDER BY c.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $contents,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取内容的回复
     */
    public static function getReplies(int $contentId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}collaboration_contents` WHERE parent_id = :parent_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':parent_id' => $contentId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT c.*, u.username as creator_name, u.avatar as creator_avatar
                FROM `{$prefix}collaboration_contents` c 
                LEFT JOIN `{$prefix}users` u ON c.creator_id = u.id 
                WHERE c.parent_id = :parent_id 
                ORDER BY c.created_at ASC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':parent_id', $contentId);
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
     * 获取用户创建的内容
     */
    public static function getByCreator(int $creatorId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}collaboration_contents` WHERE creator_id = :creator_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':creator_id' => $creatorId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT c.*, p.name as project_name
                FROM `{$prefix}collaboration_contents` c 
                LEFT JOIN `{$prefix}collaboration_projects` p ON c.project_id = p.id 
                WHERE c.creator_id = :creator_id 
                ORDER BY c.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':creator_id', $creatorId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $contents,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新内容
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'content', 'file_url', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['version'])) {
            $fields[] = "version = :version";
            $params[':version'] = $data['version'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}collaboration_contents` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除内容
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}collaboration_contents` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 创建新版本
     */
    public static function createVersion(int $contentId, array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        // 获取原内容
        $original = self::find($contentId);
        if (!$original) {
            return 0;
        }
        
        // 获取最新版本号
        $sql = "SELECT MAX(version) as max_version FROM `{$prefix}collaboration_contents` 
                WHERE project_id = :project_id AND title = :title AND creator_id = :creator_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $original['project_id'],
            ':title' => $original['title'],
            ':creator_id' => $original['creator_id']
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $newVersion = ($result['max_version'] ?? 0) + 1;
        
        // 创建新版本
        $sql = "INSERT INTO `{$prefix}collaboration_contents` (
            project_id, creator_id, type, title, content, file_url, parent_id, version, status
        ) VALUES (
            :project_id, :creator_id, :type, :title, :content, :file_url, :parent_id, :version, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $original['project_id'],
            ':creator_id' => $data['creator_id'] ?? $original['creator_id'],
            ':type' => $original['type'],
            ':title' => $original['title'],
            ':content' => $data['content'] ?? $original['content'],
            ':file_url' => $data['file_url'] ?? $original['file_url'],
            ':parent_id' => $data['parent_id'] ?? $original['parent_id'],
            ':version' => $newVersion,
            ':status' => $data['status'] ?? 'draft'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 获取内容统计
     */
    public static function getStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_content,
                    SUM(CASE WHEN type = 'novel' THEN 1 ELSE 0 END) as novel_count,
                    SUM(CASE WHEN type = 'anime' THEN 1 ELSE 0 END) as anime_count,
                    SUM(CASE WHEN type = 'music' THEN 1 ELSE 0 END) as music_count,
                    SUM(CASE WHEN type = 'comment' THEN 1 ELSE 0 END) as comment_count,
                    SUM(CASE WHEN type = 'annotation' THEN 1 ELSE 0 END) as annotation_count,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count
                FROM `{$prefix}collaboration_contents` 
                WHERE project_id = :project_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 搜索内容
     */
    public static function search(int $projectId, string $query, int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT c.*, u.username as creator_name, u.avatar as creator_avatar
                FROM `{$prefix}collaboration_contents` c 
                LEFT JOIN `{$prefix}users` u ON c.creator_id = u.id 
                WHERE c.project_id = :project_id 
                AND (c.title LIKE :query OR c.content LIKE :query)
                ORDER BY c.created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':project_id', $projectId);
        $stmt->bindValue(':query', '%' . $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}