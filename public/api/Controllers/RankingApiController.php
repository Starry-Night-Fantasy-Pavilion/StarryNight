<?php

declare(strict_types=1);

namespace api\controllers;

use app\models\RankingCache;
use app\models\Anime;
use app\models\Music;
use app\models\Novel;
use app\models\UserInvitation;

/**
 * 排行榜API控制器
 */
class RankingApiController extends BaseApiController
{
    private const VALID_TYPES = ['novel', 'anime', 'music', 'creator', 'invitation'];
    private const VALID_PERIODS = ['daily', 'weekly', 'monthly', 'all'];
    private const VALID_RANKING_TYPES = ['hot', 'new', 'favorite', 'rating'];

    /**
     * 获取排行榜数据
     */
    public function getRankings($type = 'novel', $period = 'weekly', $rankingType = 'hot', $limit = 10): void
    {
        try {
            $this->validateRankingParams($type, $period, $rankingType);
            $limit = min(max((int)$limit, 1), 100);

            $rankings = RankingCache::getOrGenerate($type, $period, $rankingType, $limit);

            $this->success([
                'type' => $type,
                'period' => $period,
                'ranking_type' => $rankingType,
                'limit' => $limit,
                'rankings' => $this->formatRankings($type, $rankings),
                'total' => count($rankings)
            ], '获取排行榜成功');
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取排行榜统计信息
     */
    public function getStats(): void
    {
        try {
            $this->success([
                'cache_stats' => RankingCache::getCacheStats(),
                'novel_stats' => Novel::getStats(),
                'anime_stats' => Anime::getStats(),
                'music_stats' => Music::getStats(),
                'invitation_stats' => UserInvitation::getGlobalStats()
            ], '获取排行榜统计成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 验证排行榜参数
     */
    private function validateRankingParams(string $type, string $period, string $rankingType): void
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('无效的排行榜类型');
        }
        if (!in_array($period, self::VALID_PERIODS, true)) {
            throw new \InvalidArgumentException('无效的统计周期');
        }
        if (!in_array($rankingType, self::VALID_RANKING_TYPES, true)) {
            throw new \InvalidArgumentException('无效的排行类型');
        }
    }

    /**
     * 格式化排行榜数据
     */
    private function formatRankings(string $type, array $rankings): array
    {
        if (empty($rankings)) {
            return [];
        }

        $formatted = [];
        $rank = 1;

        foreach ($rankings as $item) {
            $formatted[] = array_merge(
                [
                    'rank' => $rank++,
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'created_at' => $item['created_at'] ?? '',
                    'updated_at' => $item['updated_at'] ?? ''
                ],
                $this->getTypeSpecificFields($type, $item)
            );
        }

        return $formatted;
    }

    /**
     * 类型字段配置
     */
    private const TYPE_FIELDS = [
        'novel' => [
            'view_count', 'favorite_count', 'rating', 'rating_count',
            'author' => ['user_id', 'username', 'nickname']
        ],
        'anime' => [
            'view_count', 'favorite_count', 'rating', 'rating_count',
            'creator' => ['user_id', 'username', 'nickname']
        ],
        'music' => [
            'play_count', 'download_count', 'favorite_count', 'rating', 'rating_count',
            'artist' => ['user_id', 'username', 'nickname']
        ],
        'creator' => [
            'username', 'nickname', 'avatar', 'novel_count', 'anime_count',
            'music_count', 'total_views', 'total_favorites',
            'avg_novel_rating', 'avg_anime_rating', 'avg_music_rating'
        ],
        'invitation' => [
            'username', 'nickname', 'avatar', 'invitation_count',
            'total_recharge', 'total_reward'
        ]
    ];

    /**
     * 获取类型特定的字段
     */
    private function getTypeSpecificFields(string $type, array $item): array
    {
        $common = [
            'genre' => $item['genre'] ?? '',
            'description' => $item['description'] ?? '',
            'cover_image' => $item['cover_image'] ?? ''
        ];

        $config = self::TYPE_FIELDS[$type] ?? [];
        $fields = [];

        foreach ($config as $key => $value) {
            if (is_string($key)) {
                // 关联数组表示嵌套对象（如 author/creator/artist）
                $fields[$key] = $this->extractFields($item, $value);
            } else {
                // 数字索引表示普通字段
                $fields[$value] = $this->castValue($item[$value] ?? null, $value);
            }
        }

        return in_array($type, ['novel', 'anime', 'music']) ? array_merge($common, $fields) : $fields;
    }

    /**
     * 提取字段值
     */
    private function extractFields(array $source, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $key = $field === 'user_id' ? 'id' : $field;
            $result[$key] = $source[$field] ?? ($field === 'user_id' ? 0 : '');
        }
        return $result;
    }

    /**
     * 类型转换
     */
    private function castValue($value, string $field)
    {
        if ($value === null) {
            return str_starts_with($field, 'avg_') || str_starts_with($field, 'total_') ? 0.0 : 0;
        }
        if (str_starts_with($field, 'avg_')) {
            return (float)$value;
        }
        if (str_starts_with($field, 'total_') && str_contains($field, 'recharge', 'reward')) {
            return (float)$value;
        }
        return is_numeric($value) ? (int)$value : $value;
    }
}
