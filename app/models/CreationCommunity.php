<?php

namespace app\models;

use app\services\Database;
use PDO;

class CreationCommunity
{
    /**
     * 创建社区帖子
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "INSERT INTO `{$prefix}creation_community` (
            user_id, type, title, content, category, tags, attachments, is_pinned, is_locked, status
        ) VALUES (
            :user_id, :type, :title, :content, :category, :tags, :attachments, :is_pinned, :is_locked, :status
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':category' => $data['category'] ?? null,
            ':tags' => json_encode($data['tags'] ?? []),
            ':attachments' => json_encode($data['attachments'] ?? []),
            ':is_pinned' => $data['is_pinned'] ?? 0,
            ':is_locked' => $data['is_locked'] ?? 0,
            ':status' => $data['status'] ?? 'published'
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * 根据ID获取帖子
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT p.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}creation_community` p 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE p.id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            $post['tags'] = json_decode($post['tags'], true);
            $post['attachments'] = json_decode($post['attachments'], true);
        }
        
        return $post ?: null;
    }
    
    /**
     * 获取帖子列表
     */
    public static function getList(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["p.status = 'published'"];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "p.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = "p.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "p.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(p.title LIKE :search OR p.content LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['tag'])) {
            $where[] = "p.tags LIKE :tag";
            $params[':tag'] = '%' . $filters['tag'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_community` p WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}creation_community` p 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE {$whereClause} 
                ORDER BY p.is_pinned DESC, p.created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['tags'] = json_decode($post['tags'], true);
            $post['attachments'] = json_decode($post['attachments'], true);
        }
        
        return [
            'data' => $posts,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取用户的帖子列表
     */
    public static function getByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $offset = ($page - 1) * $perPage;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM `{$prefix}creation_community` WHERE user_id = :user_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':user_id' => $userId]);
        $total = $countStmt->fetchColumn();
        
        // 获取数据
        $sql = "SELECT * FROM `{$prefix}creation_community` 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['tags'] = json_decode($post['tags'], true);
            $post['attachments'] = json_decode($post['attachments'], true);
        }
        
        return [
            'data' => $posts,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * 更新帖子
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $fields = [];
        $params = [':id' => $id];
        
        foreach (['title', 'content', 'category', 'is_pinned', 'is_locked', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['tags'])) {
            $fields[] = "tags = :tags";
            $params[':tags'] = json_encode($data['tags']);
        }
        
        if (isset($data['attachments'])) {
            $fields[] = "attachments = :attachments";
            $params[':attachments'] = json_encode($data['attachments']);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE `{$prefix}creation_community` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * 删除帖子
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "DELETE FROM `{$prefix}creation_community` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加查看次数
     */
    public static function incrementView(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}creation_community` SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加点赞数
     */
    public static function incrementLike(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}creation_community` SET like_count = like_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 增加回复数
     */
    public static function incrementReply(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "UPDATE `{$prefix}creation_community` SET reply_count = reply_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * 获取热门帖子
     */
    public static function getPopular(int $limit = 10, string $type = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $where = ["status = 'published'"];
        $params = [];
        
        if ($type) {
            $where[] = "type = :type";
            $params[':type'] = $type;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT p.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}creation_community` p 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE {$whereClause} 
                ORDER BY p.like_count DESC, p.view_count DESC, p.reply_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['tags'] = json_decode($post['tags'], true);
            $post['attachments'] = json_decode($post['attachments'], true);
        }
        
        return $posts;
    }
    
    /**
     * 获取置顶帖子
     */
    public static function getPinned(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT p.*, u.username as user_name, u.avatar as user_avatar
                FROM `{$prefix}creation_community` p 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE p.status = 'published' AND p.is_pinned = 1 
                ORDER BY p.created_at DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($posts as &$post) {
            $post['tags'] = json_decode($post['tags'], true);
            $post['attachments'] = json_decode($post['attachments'], true);
        }
        
        return $posts;
    }
    
    /**
     * 获取社区统计
     */
    public static function getStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT 
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN type = 'question' THEN 1 ELSE 0 END) as question_count,
                    SUM(CASE WHEN type = 'discussion' THEN 1 ELSE 0 END) as discussion_count,
                    SUM(CASE WHEN type = 'tutorial' THEN 1 ELSE 0 END) as tutorial_count,
                    SUM(CASE WHEN type = 'showcase' THEN 1 ELSE 0 END) as showcase_count,
                    SUM(view_count) as total_views,
                    SUM(like_count) as total_likes,
                    SUM(reply_count) as total_replies,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT category) as unique_categories
                FROM `{$prefix}creation_community` 
                WHERE status = 'published'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取热门标签
     */
    public static function getPopularTags(int $limit = 20): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $sql = "SELECT tag, COUNT(*) as usage_count
                FROM (
                    SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', n), ',', -1)) as tag
                    FROM `{$prefix}creation_community` 
                    WHERE tags IS NOT NULL AND tags != '[]' 
                    AND status = 'published'
                ) as tag_counts
                WHERE tag != ''
                GROUP BY tag 
                ORDER BY usage_count DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}