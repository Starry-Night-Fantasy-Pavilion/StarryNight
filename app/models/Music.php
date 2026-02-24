<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 音乐作品模型
 */
class Music
{
    /**
     * 创建音乐作品
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}music` 
                (user_id, title, genre, type, theme, duration, status, cover_image, audio_url, description, tags, play_count, download_count, favorite_count, rating, rating_count) 
                VALUES (:user_id, :title, :genre, :type, :theme, :duration, :status, :cover_image, :audio_url, :description, :tags, :play_count, :download_count, :favorite_count, :rating, :rating_count)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':title' => $data['title'],
            ':genre' => $data['genre'] ?? null,
            ':type' => $data['type'] ?? null,
            ':theme' => $data['theme'] ?? null,
            ':duration' => $data['duration'] ?? 0,
            ':status' => $data['status'] ?? 'draft',
            ':cover_image' => $data['cover_image'] ?? null,
            ':audio_url' => $data['audio_url'] ?? null,
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':play_count' => $data['play_count'] ?? 0,
            ':download_count' => $data['download_count'] ?? 0,
            ':favorite_count' => $data['favorite_count'] ?? 0,
            ':rating' => $data['rating'] ?? 0.00,
            ':rating_count' => $data['rating_count'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取音乐作品
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT m.*, u.username, u.nickname 
                FROM `{$prefix}music` m 
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id 
                WHERE m.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取音乐作品列表
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
            $where[] = "m.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['genre'])) {
            $where[] = "m.genre = :genre";
            $params[':genre'] = $filters['genre'];
        }

        if (!empty($filters['status'])) {
            $where[] = "m.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(m.title LIKE :search OR m.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT m.*, u.username, u.nickname 
                FROM `{$prefix}music` m 
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY m.created_at DESC 
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
     * 获取热门音乐排行榜
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
                $dateCondition = "AND DATE(mp.created_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND mp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND mp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT m.*, u.username, u.nickname,
                       COUNT(mp.id) as play_count,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COUNT(DISTINCT md.id) as download_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating,
                       COUNT(DISTINCT wr.id) as rating_count
                FROM `{$prefix}music` m
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id
                LEFT JOIN `{$prefix}music_plays` mp ON m.id = mp.music_id {$dateCondition}
                LEFT JOIN `{$prefix}work_favorites` wf ON m.id = wf.work_id AND wf.work_type = 'music'
                LEFT JOIN `{$prefix}work_ratings` wr ON m.id = wr.work_id AND wr.work_type = 'music'
                LEFT JOIN `{$prefix}work_views` wv ON m.id = wv.work_id AND wv.work_type = 'music'
                WHERE m.status = 'published'
                GROUP BY m.id
                ORDER BY play_count DESC, favorite_count DESC, avg_rating DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取新作音乐排行榜
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
                $dateCondition = "AND DATE(m.created_at) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $dateCondition = "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT m.*, u.username, u.nickname,
                       COUNT(mp.id) as play_count,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating
                FROM `{$prefix}music` m
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id
                LEFT JOIN `{$prefix}music_plays` mp ON m.id = mp.music_id
                LEFT JOIN `{$prefix}work_favorites` wf ON m.id = wf.work_id AND wf.work_type = 'music'
                LEFT JOIN `{$prefix}work_ratings` wr ON m.id = wr.work_id AND wr.work_type = 'music'
                WHERE m.status = 'published' {$dateCondition}
                GROUP BY m.id
                ORDER BY m.created_at DESC, play_count DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取收藏音乐排行榜
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

        $sql = "SELECT m.*, u.username, u.nickname,
                       COUNT(DISTINCT wf.id) as favorite_count,
                       COUNT(mp.id) as play_count,
                       COALESCE(AVG(wr.rating), 0) as avg_rating
                FROM `{$prefix}music` m
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id
                LEFT JOIN `{$prefix}work_favorites` wf ON m.id = wf.work_id AND wf.work_type = 'music' {$dateCondition}
                LEFT JOIN `{$prefix}music_plays` mp ON m.id = mp.music_id
                LEFT JOIN `{$prefix}work_ratings` wr ON m.id = wr.work_id AND wr.work_type = 'music'
                WHERE m.status = 'published'
                GROUP BY m.id
                HAVING favorite_count > 0
                ORDER BY favorite_count DESC, play_count DESC
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新音乐作品
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
            'title', 'genre', 'type', 'theme', 'duration', 'status', 
            'cover_image', 'audio_url', 'description', 'tags', 
            'play_count', 'download_count', 'favorite_count', 
            'rating', 'rating_count'
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

        $sql = "UPDATE `{$prefix}music` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 删除音乐作品
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}music` WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * 增加播放量
     *
     * @param int $id
     * @param int $userId
     * @param int $playDuration
     * @param bool $completed
     * @param string $ipAddress
     * @param string $userAgent
     * @return bool
     */
    public static function addPlay(int $id, ?int $userId = null, int $playDuration = 0, bool $completed = false, string $ipAddress = '', string $userAgent = ''): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        // 记录播放记录
        $sql = "INSERT INTO `{$prefix}music_plays` (user_id, music_id, play_duration, completed, ip_address, user_agent) 
                VALUES (:user_id, :music_id, :play_duration, :completed, :ip_address, :user_agent)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':music_id' => $id,
            ':play_duration' => $playDuration,
            ':completed' => $completed ? 1 : 0,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);

        // 更新播放量
        $sql = "UPDATE `{$prefix}music` SET play_count = play_count + 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 增加下载量
     *
     * @param int $id
     * @return bool
     */
    public static function addDownload(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}music` SET download_count = download_count + 1 WHERE id = :id";
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
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    SUM(play_count) as total_plays,
                    SUM(download_count) as total_downloads,
                    SUM(favorite_count) as total_favorites,
                    AVG(rating) as avg_rating
                FROM `{$prefix}music`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}