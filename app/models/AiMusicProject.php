<?php

namespace App\Models;

use App\Services\Database;
use PDO;

class AiMusicProject
{
    private $table = 'ai_music_project';

    private function getDb(): PDO
    {
        return Database::pdo();
    }

    /**
     * 创建音乐项目
     */
    public function create(array $data)
    {
        $sql = "INSERT INTO {$this->table} (
            user_id, title, genre, description, status, bpm, key_signature,
            duration, cover_image, tags, is_public
        ) VALUES (
            :user_id, :title, :genre, :description, :status, :bpm, :key_signature,
            :duration, :cover_image, :tags, :is_public
        )";
        
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':genre' => $data['genre'] ?? null,
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'] ?? 1,
            ':bpm' => $data['bpm'] ?? null,
            ':key_signature' => $data['key_signature'] ?? null,
            ':duration' => $data['duration'] ?? null,
            ':cover_image' => $data['cover_image'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':is_public' => $data['is_public'] ?? 0
        ]);
    }

    /**
     * 获取音乐项目详情
     */
    public function getById(int $id)
    {
        $sql = "SELECT p.*, u.username, u.avatar
                FROM {$this->table} p
                LEFT JOIN user u ON p.user_id = u.id
                WHERE p.id = :id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户音乐项目列表
     */
    public function getByUserId(int $userId, int $page = 1, int $limit = 20, string $status = null)
    {
        $offset = ($page - 1) * $limit;
        $whereClause = "WHERE p.user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($status) {
            $whereClause .= " AND p.status = :status";
            $params[':status'] = $status;
        }
        
        $sql = "SELECT p.*,
                       (SELECT COUNT(*) FROM ai_music_track WHERE project_id = p.id) as track_count,
                       (SELECT COUNT(*) FROM ai_music_export WHERE project_id = p.id) as export_count
                FROM {$this->table} p
                {$whereClause}
                ORDER BY p.updated_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取公开音乐项目列表
     */
    public function getPublicProjects(int $page = 1, int $limit = 20, string $genre = null)
    {
        $offset = ($page - 1) * $limit;
        $whereClause = "WHERE p.is_public = 1 AND p.status = 3";
        $params = [];
        
        if ($genre) {
            $whereClause .= " AND p.genre = :genre";
            $params[':genre'] = $genre;
        }
        
        $sql = "SELECT p.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM ai_music_track WHERE project_id = p.id) as track_count,
                       (SELECT COUNT(*) FROM ai_music_favorite WHERE project_id = p.id) as favorite_count
                FROM {$this->table} p
                LEFT JOIN user u ON p.user_id = u.id
                {$whereClause}
                ORDER BY p.like_count DESC, p.updated_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新音乐项目
     */
    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'title', 'genre', 'description', 'status', 'bpm', 'key_signature',
            'duration', 'cover_image', 'tags', 'is_public', 'view_count', 'like_count'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除音乐项目
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->getDb()->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 搜索音乐项目
     */
    public function search(string $keyword, int $page = 1, int $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM ai_music_track WHERE project_id = p.id) as track_count
                FROM {$this->table} p
                LEFT JOIN user u ON p.user_id = u.id
                WHERE p.is_public = 1 AND p.status = 3
                  AND (p.title LIKE :keyword OR p.description LIKE :keyword OR p.tags LIKE :keyword)
                ORDER BY p.like_count DESC, p.updated_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getDb()->prepare($sql);
        $searchTerm = "%{$keyword}%";
        $stmt->bindValue(':keyword', $searchTerm);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目统计信息
     */
    public function getProjectStats(int $projectId)
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM ai_music_track WHERE project_id = :project_id) as track_count,
                    (SELECT COUNT(*) FROM ai_music_lyrics WHERE project_id = :project_id) as lyrics_count,
                    (SELECT COUNT(*) FROM ai_music_vocal WHERE project_id = :project_id) as vocal_count,
                    (SELECT COUNT(*) FROM ai_music_export WHERE project_id = :project_id) as export_count,
                    (SELECT COUNT(*) FROM ai_music_collaboration WHERE project_id = :project_id) as collaborator_count,
                    (SELECT COUNT(*) FROM ai_music_comment WHERE project_id = :project_id) as comment_count,
                    (SELECT COUNT(*) FROM ai_music_favorite WHERE project_id = :project_id) as favorite_count,
                    (SELECT COUNT(*) FROM ai_music_share WHERE project_id = :project_id) as share_count
                FROM DUAL";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取用户项目统计
     */
    public function getUserProjectStats(int $userId)
    {
        $sql = "SELECT
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as published_count,
                    SUM(view_count) as total_views,
                    SUM(like_count) as total_likes
                FROM {$this->table}
                WHERE user_id = :user_id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取热门音乐项目
     */
    public function getPopularProjects(int $limit = 10, string $genre = null)
    {
        $whereClause = "WHERE p.is_public = 1 AND p.status = 3";
        $params = [];
        
        if ($genre) {
            $whereClause .= " AND p.genre = :genre";
            $params[':genre'] = $genre;
        }
        
        $sql = "SELECT p.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM ai_music_favorite WHERE project_id = p.id) as favorite_count
                FROM {$this->table} p
                LEFT JOIN user u ON p.user_id = u.id
                {$whereClause}
                ORDER BY p.like_count DESC, p.view_count DESC
                LIMIT :limit";
        
        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取最新音乐项目
     */
    public function getLatestProjects(int $limit = 10, string $genre = null)
    {
        $whereClause = "WHERE p.is_public = 1 AND p.status = 3";
        $params = [];
        
        if ($genre) {
            $whereClause .= " AND p.genre = :genre";
            $params[':genre'] = $genre;
        }
        
        $sql = "SELECT p.*, u.username, u.avatar,
                       (SELECT COUNT(*) FROM ai_music_favorite WHERE project_id = p.id) as favorite_count
                FROM {$this->table} p
                LEFT JOIN user u ON p.user_id = u.id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->getDb()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 检查用户是否有项目访问权限
     */
    public function checkUserPermission(int $projectId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE id = :project_id AND user_id = :user_id";
        
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * 复制音乐项目
     */
    public function duplicateProject(int $projectId, int $newUserId): ?int
    {
        // 获取原项目信息
        $originalProject = $this->getById($projectId);
        if (!$originalProject) {
            return null;
        }

        // 创建新项目
        $newProjectData = [
            'user_id' => $newUserId,
            'title' => $originalProject['title'] . ' (副本)',
            'genre' => $originalProject['genre'],
            'description' => $originalProject['description'],
            'status' => 1, // 设为草稿状态
            'bpm' => $originalProject['bpm'],
            'key_signature' => $originalProject['key_signature'],
            'tags' => $originalProject['tags'],
            'is_public' => 0
        ];

        if ($this->create($newProjectData)) {
            return $this->getDb()->lastInsertId();
        }

        return null;
    }

    /**
     * 获取项目总数
     */
    public function getTotalCount(string $status = null): int
    {
        $whereClause = "";
        $params = [];
        
        if ($status) {
            $whereClause = "WHERE status = :status";
            $params[':status'] = $status;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * 获取用户项目总数
     */
    public function getUserTotalCount(int $userId, string $status = null): int
    {
        $whereClause = "WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($status) {
            $whereClause .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
}