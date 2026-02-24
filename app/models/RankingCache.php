<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 排行榜缓存模型
 */
class RankingCache
{
    /**
     * 创建排行榜缓存
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}ranking_cache` 
                (type, period, period_start, period_end, rank_data) 
                VALUES (:type, :period, :period_start, :period_end, :rank_data)
                ON DUPLICATE KEY UPDATE 
                rank_data = VALUES(rank_data),
                updated_at = CURRENT_TIMESTAMP";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':type' => $data['type'],
            ':period' => $data['period'],
            ':period_start' => $data['period_start'],
            ':period_end' => $data['period_end'],
            ':rank_data' => json_encode($data['rank_data'])
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 获取排行榜缓存
     *
     * @param string $type
     * @param string $period
     * @param string $periodStart
     * @param string $periodEnd
     * @return array|false
     */
    public static function get(string $type, string $period, string $periodStart, string $periodEnd)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}ranking_cache` 
                WHERE type = :type AND period = :period 
                AND period_start = :period_start AND period_end = :period_end";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':type' => $type,
            ':period' => $period,
            ':period_start' => $periodStart,
            ':period_end' => $periodEnd
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['rank_data'] = json_decode($result['rank_data'], true);
        }
        return $result;
    }

    /**
     * 获取或生成排行榜数据
     *
     * @param string $type 排行榜类型 (novel/anime/music/creator/invitation)
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param string $rankingType 排行类型 (hot/new/favorite)
     * @param int $limit
     * @return array
     */
    public static function getOrGenerate(string $type, string $period = 'weekly', string $rankingType = 'hot', int $limit = 10): array
    {
        // 计算周期日期范围
        $periodDates = self::calculatePeriodDates($period);
        
        // 尝试从缓存获取
        $cacheKey = "{$type}_{$rankingType}_{$period}";
        $cached = self::get($cacheKey, $period, $periodDates['start'], $periodDates['end']);
        
        if ($cached && (time() - strtotime($cached['updated_at'])) < 3600) { // 1小时缓存
            return $cached['rank_data'];
        }

        // 生成新的排行榜数据
        $rankData = self::generateRankingData($type, $period, $rankingType, $limit);

        // 保存到缓存
        self::create([
            'type' => $cacheKey,
            'period' => $period,
            'period_start' => $periodDates['start'],
            'period_end' => $periodDates['end'],
            'rank_data' => $rankData
        ]);

        return $rankData;
    }

    /**
     * 生成排行榜数据
     *
     * @param string $type
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateRankingData(string $type, string $period, string $rankingType, int $limit): array
    {
        switch ($type) {
            case 'novel':
                return self::generateNovelRanking($period, $rankingType, $limit);
            case 'anime':
                return self::generateAnimeRanking($period, $rankingType, $limit);
            case 'music':
                return self::generateMusicRanking($period, $rankingType, $limit);
            case 'creator':
                return self::generateCreatorRanking($period, $rankingType, $limit);
            case 'invitation':
                return self::generateInvitationRanking($period, $rankingType, $limit);
            default:
                return [];
        }
    }

    /**
     * 生成小说排行榜
     *
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateNovelRanking(string $period, string $rankingType, int $limit): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = self::getDateCondition($period, 'n.created_at');
        $orderBy = self::getOrderBy($rankingType);

        $sql = "SELECT n.id, n.title, n.genre, n.description, n.cover_image, n.view_count, n.favorite_count, n.rating, n.rating_count,
                       u.username, u.nickname, u.avatar,
                       n.created_at, n.updated_at
                FROM `{$prefix}novels` n
                LEFT JOIN `{$prefix}users` u ON n.user_id = u.id
                WHERE n.status = 'published' {$dateCondition}
                ORDER BY {$orderBy}
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 生成动漫排行榜
     *
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateAnimeRanking(string $period, string $rankingType, int $limit): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = self::getDateCondition($period, 'a.created_at');
        $orderBy = self::getOrderBy($rankingType);

        $sql = "SELECT a.id, a.title, a.genre, a.description, a.cover_image, a.view_count, a.favorite_count, a.rating, a.rating_count,
                       u.username, u.nickname, u.avatar,
                       a.created_at, a.updated_at
                FROM `{$prefix}animes` a
                LEFT JOIN `{$prefix}users` u ON a.user_id = u.id
                WHERE a.status = 'published' {$dateCondition}
                ORDER BY {$orderBy}
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 生成音乐排行榜
     *
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateMusicRanking(string $period, string $rankingType, int $limit): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = self::getDateCondition($period, 'm.created_at');
        $orderBy = self::getOrderBy($rankingType, 'play_count');

        $sql = "SELECT m.id, m.title, m.genre, m.description, m.cover_image, m.play_count, m.favorite_count, m.rating, m.rating_count,
                       u.username, u.nickname, u.avatar,
                       m.created_at, m.updated_at
                FROM `{$prefix}music` m
                LEFT JOIN `{$prefix}users` u ON m.user_id = u.id
                WHERE m.status = 'published' {$dateCondition}
                ORDER BY {$orderBy}
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 生成创作者排行榜
     *
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateCreatorRanking(string $period, string $rankingType, int $limit): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $dateCondition = self::getDateCondition($period, 'created_at');
        $orderBy = self::getCreatorOrderBy($rankingType);

        $sql = "SELECT u.id, u.username, u.nickname, u.avatar, u.created_at,
                       COUNT(DISTINCT n.id) as novel_count,
                       COUNT(DISTINCT a.id) as anime_count,
                       COUNT(DISTINCT m.id) as music_count,
                       COALESCE(SUM(n.view_count), 0) + COALESCE(SUM(a.view_count), 0) + COALESCE(SUM(m.play_count), 0) as total_views,
                       COALESCE(SUM(n.favorite_count), 0) + COALESCE(SUM(a.favorite_count), 0) + COALESCE(SUM(m.favorite_count), 0) as total_favorites,
                       COALESCE(AVG(n.rating), 0) as avg_novel_rating,
                       COALESCE(AVG(a.rating), 0) as avg_anime_rating,
                       COALESCE(AVG(m.rating), 0) as avg_music_rating
                FROM `{$prefix}users` u
                LEFT JOIN `{$prefix}novels` n ON u.id = n.user_id AND n.status = 'published' {$dateCondition}
                LEFT JOIN `{$prefix}animes` a ON u.id = a.user_id AND a.status = 'published' {$dateCondition}
                LEFT JOIN `{$prefix}music` m ON u.id = m.user_id AND m.status = 'published' {$dateCondition}
                GROUP BY u.id
                HAVING novel_count > 0 OR anime_count > 0 OR music_count > 0
                ORDER BY {$orderBy}
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 生成邀请排行榜
     *
     * @param string $period
     * @param string $rankingType
     * @param int $limit
     * @return array
     */
    private static function generateInvitationRanking(string $period, string $rankingType, int $limit): array
    {
        return UserInvitation::getRanking($period, $rankingType, $limit);
    }

    /**
     * 计算周期日期范围
     *
     * @param string $period
     * @return array
     */
    private static function calculatePeriodDates(string $period): array
    {
        $now = new \DateTime();
        
        switch ($period) {
            case 'daily':
                return [
                    'start' => $now->format('Y-m-d'),
                    'end' => $now->format('Y-m-d')
                ];
            case 'weekly':
                $weekStart = clone $now;
                $weekStart->modify('Monday this week');
                $weekEnd = clone $now;
                $weekEnd->modify('Sunday this week');
                return [
                    'start' => $weekStart->format('Y-m-d'),
                    'end' => $weekEnd->format('Y-m-d')
                ];
            case 'monthly':
                return [
                    'start' => $now->format('Y-m-01'),
                    'end' => $now->format('Y-m-t')
                ];
            case 'all':
                return [
                    'start' => '2020-01-01',
                    'end' => $now->format('Y-m-d')
                ];
            default:
                return [
                    'start' => $now->format('Y-m-d'),
                    'end' => $now->format('Y-m-d')
                ];
        }
    }

    /**
     * 获取日期条件
     *
     * @param string $period
     * @param string $dateField
     * @return string
     */
    private static function getDateCondition(string $period, string $dateField): string
    {
        switch ($period) {
            case 'daily':
                return "AND DATE({$dateField}) = CURDATE()";
            case 'weekly':
                return "AND {$dateField} >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'monthly':
                return "AND {$dateField} >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case 'all':
                return "";
            default:
                return "";
        }
    }

    /**
     * 获取排序条件
     *
     * @param string $rankingType
     * @param string $primaryField
     * @return string
     */
    private static function getOrderBy(string $rankingType, string $primaryField = 'view_count'): string
    {
        switch ($rankingType) {
            case 'hot':
                return "{$primaryField} DESC, favorite_count DESC, rating DESC";
            case 'new':
                return "created_at DESC, {$primaryField} DESC";
            case 'favorite':
                return "favorite_count DESC, {$primaryField} DESC, rating DESC";
            case 'rating':
                return "rating DESC, rating_count DESC, {$primaryField} DESC";
            default:
                return "{$primaryField} DESC, favorite_count DESC";
        }
    }

    /**
     * 获取创作者排序条件
     *
     * @param string $rankingType
     * @return string
     */
    private static function getCreatorOrderBy(string $rankingType): string
    {
        switch ($rankingType) {
            case 'works':
                return "(novel_count + anime_count + music_count) DESC, total_views DESC";
            case 'views':
                return "total_views DESC, total_favorites DESC";
            case 'favorites':
                return "total_favorites DESC, total_views DESC";
            case 'rating':
                return "(avg_novel_rating + avg_anime_rating + avg_music_rating) DESC, total_views DESC";
            default:
                return "total_views DESC, total_favorites DESC";
        }
    }

    /**
     * 清理过期缓存
     *
     * @param int $days 保留天数
     * @return int 清理的记录数
     */
    public static function cleanExpiredCache(int $days = 7): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}ranking_cache` 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * 获取缓存统计
     *
     * @return array
     */
    public static function getCacheStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_cache,
                    COUNT(DISTINCT type) as unique_types,
                    COUNT(DISTINCT period) as unique_periods,
                    COUNT(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as fresh_cache,
                    COUNT(CASE WHEN updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as stale_cache
                FROM `{$prefix}ranking_cache`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}