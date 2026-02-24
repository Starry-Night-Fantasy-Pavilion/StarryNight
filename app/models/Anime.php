<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫作品模型
 */
class Anime
{
    /**
     * 创建动漫作品
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}animes` 
                (user_id, title, genre, type, theme, target_episodes, current_episodes, episode_duration, status, cover_image, description, tags, view_count, favorite_count, rating, rating_count) 
                VALUES (:user_id, :title, :genre, :type, :theme, :target_episodes, :current_episodes, :episode_duration, :status, :cover_image, :description, :tags, :view_count, :favorite_count, :rating, :rating_count)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':genre' => $data['genre'] ?? null,
            ':type' => $data['type'] ?? null,
            ':theme' => $data['theme'] ?? null,
            ':target_episodes' => $data['target_episodes'] ?? 0,
            ':current_episodes' => $data['current_episodes'] ?? 0,
            ':episode_duration' => $data['episode_duration'] ?? 20,
            ':status' => $data['status'] ?? 'draft',
            ':cover_image' => $data['cover_image'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':view_count' => $data['view_count'] ?? 0,
            ':favorite_count' => $data['favorite_count'] ?? 0,
            ':rating' => $data['rating'] ?? 0.00,
            ':rating_count' => $data['rating_count'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取动漫作品
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT a.*, u.username, u.nickname 
                FROM `{$prefix}animes` a 
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id 
                WHERE a.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取动漫作品列表
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "a.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['genre'])) {
            $where[] = "a.genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(a.title LIKE :search OR a.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT a.*, u.username, u.nickname 
                FROM `{$prefix}animes` a 
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY a.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取热门动漫排行榜
     *
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param int $limit
     * @return array
     */
    public static function getHotRanking(string $period = 'weekly', int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = "";
        $params = [];

        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(wv.created_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND wv.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND wv.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT a.*, u.username, u.nickname,
                       COUNT(wv.id) as view_count,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating,
                       COUNT(DISTINCT wr.id) as rating_count
                FROM `{$prefix}animes` a
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id
                LEFT JOIN `{$prefix}work_views` wv ON a.id = wv.work_id AND wv.work_type = 'anime' {$dateCondition}
                LEFT JOIN `{$prefix}work_favorites` wf ON a.id = wf.work_id AND wf.work_type = 'anime'
                LEFT JOIN `{$prefix}work_ratings` wr ON a.id = wr.work_id AND wr.work_type = 'anime'
                WHERE a.status = 'published'
                GROUP BY a.id
                ORDER BY view_count DESC, favorite_count DESC, avg_rating DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取新作动漫排行榜
     *
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param int $limit
     * @return array
     */
    public static function getNewRanking(string $period = 'weekly', int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = "";
        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(a.created_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT a.*, u.username, u.nickname,
                       COUNT(wv.id) as view_count,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating
                FROM `{$prefix}animes` a
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id
                LEFT JOIN `{$prefix}work_views` wv ON a.id = wv.work_id AND wv.work_type = 'anime'
                LEFT JOIN `{$prefix}work_favorites` wf ON a.id = wf.work_id AND wf.work_type = 'anime'
                LEFT JOIN `{$prefix}work_ratings` wr ON a.id = wr.work_id AND wr.work_type = 'anime'
                WHERE a.status = 'published' {$dateCondition}
                GROUP BY a.id
                ORDER BY a.created_at DESC, view_count DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取收藏动漫排行榜
     *
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param int $limit
     * @return array
     */
    public static function getFavoriteRanking(string $period = 'weekly', int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = "";
        $params = [];

        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(wf.created_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND wf.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND wf.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT a.*, u.username, u.nickname,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COUNT(wv.id) as view_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating
                FROM `{$prefix}animes` a
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id
                LEFT JOIN `{$prefix}work_favorites` wf ON a.id = wf.work_id AND wf.work_type = 'anime' {$dateCondition}
                LEFT JOIN `{$prefix}work_views` wv ON a.id = wv.work_id AND wv.work_type = 'anime'
                LEFT JOIN `{$prefix}work_ratings` wr ON a.id = wr.work_id AND wr.work_type = 'anime'
                WHERE a.status = 'published'
                GROUP BY a.id
                HAVING favorite_count > 0
                ORDER BY favorite_count DESC, view_count DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新动漫作品
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $fields = [];
        $params = [':id' => $id];

        $allowedFields = [
            'title', 'genre', 'type', 'theme', 'target_episodes', 
            'current_episodes', 'episode_duration', 'status', 
            'cover_image', 'description', 'tags', 'view_count', 
            'favorite_count', 'rating', 'rating_count'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}animes` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 删除动漫作品
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}animes` WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * 增加浏览量
     *
     * @param int $id
     * @param int $userId
     * @param string $ipAddress
     * @param string $userAgent
     * @return bool
     */
    public static function addView(int $id, ?int $userId = null, string $ipAddress = '', string $userAgent = ''): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 记录浏览记录
        $sql = "INSERT INTO `{$prefix}work_views` (user_id, work_type, work_id, ip_address, user_agent) 
                VALUES (:user_id, 'anime', :work_id, :ip_address, :user_agent)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':work_id' => $id,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);

        // 更新浏览量
        $sql = "UPDATE `{$prefix}animes` SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 获取统计信息
     *
     * @return array
     */
    public static function getStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'published' THEN 1 END) as published,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                    COUNT(CASE WHEN status = 'production' THEN 1 END) as in_production,
                    SUM(view_count) as total_views,
                    SUM(favorite_count) as total_favorites,
                    AVG(rating) as avg_rating
                FROM `{$prefix}animes`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}