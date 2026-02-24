<?php

namespace app\frontend\controller;

use app\models\RankingCache;
use app\models\Anime;
use app\models\Music;
use app\models\Novel;
use app\models\UserInvitation;
use app\models\User;

/**
 * 排行榜API控制器
 */
class RankingController
{
    /**
     * 排行榜首页
     */
    public function index()
    {
        try {
            header('Content-Type: text/html; charset=utf-8');
            
            $siteName = (string) get_env('APP_NAME', '星夜阁');
            
            // 使用主题系统渲染页面
            $themeManager = new \app\services\ThemeManager();
            $theme = $themeManager->loadActiveThemeInstance();
            
            if ($theme) {
                $content = $theme->renderTemplate('ranking', [
                    'site_name' => $siteName,
                ]);
                
                echo $theme->renderTemplate('layout', [
                    'title' => '排行榜 - ' . $siteName,
                    'site_name' => $siteName,
                    'page_class' => 'page-ranking',
                    'current_page' => 'ranking',
                    'content' => $content,
                ]);
            } else {
                // 如果没有主题，使用简单视图
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>排行榜</title></head><body><h1>排行榜</h1><p>功能开发中...</p></body></html>';
            }
        } catch (\Exception $e) {
            error_log('排行榜页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }

    /**
     * 返回JSON错误响应
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     */
    private function error(string $message, int $code = 400)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 返回JSON成功响应
     * @param array $data 响应数据
     * @param string $message 成功消息
     */
    private function success(array $data = [], string $message = '操作成功')
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    /**
     * 获取排行榜数据
     * 
     * @param string $type 排行榜类型 (novel/anime/music/creator/invitation)
     * @param string $period 统计周期 (daily/weekly/monthly/all)
     * @param string $rankingType 排行类型 (hot/new/favorite)
     * @param int $limit 返回数量限制
     */
    public function getRankings($type = 'novel', $period = 'weekly', $rankingType = 'hot', $limit = 10)
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // 验证参数
            $validTypes = ['novel', 'anime', 'music', 'creator', 'invitation'];
            $validPeriods = ['daily', 'weekly', 'monthly', 'all'];
            $validRankingTypes = ['hot', 'new', 'favorite', 'rating'];
            
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException('无效的排行榜类型');
            }
            
            if (!in_array($period, $validPeriods)) {
                throw new \InvalidArgumentException('无效的统计周期');
            }
            
            if (!in_array($rankingType, $validRankingTypes)) {
                throw new \InvalidArgumentException('无效的排行类型');
            }
            
            $limit = min(max(intval($limit), 1), 100); // 限制在1-100之间
            
            // 获取排行榜数据
            $rankings = RankingCache::getOrGenerate($type, $period, $rankingType, $limit);
            
            // 格式化数据
            $formattedRankings = $this->formatRankings($type, $rankings);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'period' => $period,
                    'ranking_type' => $rankingType,
                    'limit' => $limit,
                    'rankings' => $formattedRankings,
                    'total' => count($formattedRankings)
                ],
                'message' => '获取排行榜成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('获取排行榜失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取小说排行榜
     */
    public function getNovelRankings($period = 'weekly', $rankingType = 'hot', $limit = 10)
    {
        $this->getRankings('novel', $period, $rankingType, $limit);
    }
    
    /**
     * 获取动漫排行榜
     */
    public function getAnimeRankings($period = 'weekly', $rankingType = 'hot', $limit = 10)
    {
        $this->getRankings('anime', $period, $rankingType, $limit);
    }
    
    /**
     * 获取音乐排行榜
     */
    public function getMusicRankings($period = 'weekly', $rankingType = 'hot', $limit = 10)
    {
        $this->getRankings('music', $period, $rankingType, $limit);
    }
    
    /**
     * 获取创作者排行榜
     */
    public function getCreatorRankings($period = 'weekly', $rankingType = 'views', $limit = 10)
    {
        $this->getRankings('creator', $period, $rankingType, $limit);
    }
    
    /**
     * 获取邀请排行榜
     */
    public function getInvitationRankings($period = 'weekly', $rankingType = 'count', $limit = 10)
    {
        $this->getRankings('invitation', $period, $rankingType, $limit);
    }
    
    /**
     * 获取综合排行榜首页数据
     */
    public function getHomePage()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $period = $_GET['period'] ?? 'weekly';
            $limit = min(max(intval($_GET['limit'] ?? 5), 1), 20);
            
            // 获取各类型的热门排行榜
            $novelHot = $this->getRankingsData('novel', $period, 'hot', $limit);
            $animeHot = $this->getRankingsData('anime', $period, 'hot', $limit);
            $musicHot = $this->getRankingsData('music', $period, 'hot', $limit);
            $creatorTop = $this->getRankingsData('creator', $period, 'views', $limit);
            $invitationTop = $this->getRankingsData('invitation', $period, 'count', $limit);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'novel_hot' => $this->formatRankings('novel', $novelHot),
                    'anime_hot' => $this->formatRankings('anime', $animeHot),
                    'music_hot' => $this->formatRankings('music', $musicHot),
                    'creator_top' => $this->formatRankings('creator', $creatorTop),
                    'invitation_top' => $this->formatRankings('invitation', $invitationTop)
                ],
                'message' => '获取综合排行榜成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            $this->error('获取综合排行榜失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取排行榜数据（内部方法）
     */
    private function getRankingsData($type, $period, $rankingType, $limit)
    {
        return RankingCache::getOrGenerate($type, $period, $rankingType, $limit);
    }
    
    /**
     * 格式化排行榜数据
     */
    private function formatRankings($type, $rankings)
    {
        if (empty($rankings)) {
            return [];
        }
        
        $formatted = [];
        $rank = 1;
        
        foreach ($rankings as $item) {
            $formattedItem = [
                'rank' => $rank++,
                'id' => $item['id'],
                'title' => $item['title'] ?? '',
                'created_at' => $item['created_at'] ?? '',
                'updated_at' => $item['updated_at'] ?? ''
            ];
            
            switch ($type) {
                case 'novel':
                    $formattedItem = array_merge($formattedItem, [
                        'genre' => $item['genre'] ?? '',
                        'description' => $item['description'] ?? '',
                        'cover_image' => $item['cover_image'] ?? '',
                        'view_count' => intval($item['view_count'] ?? 0),
                        'favorite_count' => intval($item['favorite_count'] ?? 0),
                        'rating' => floatval($item['rating'] ?? 0),
                        'rating_count' => intval($item['rating_count'] ?? 0),
                        'author' => [
                            'id' => $item['user_id'],
                            'username' => $item['username'] ?? '',
                            'nickname' => $item['nickname'] ?? ''
                        ]
                    ]);
                    break;
                    
                case 'anime':
                    $formattedItem = array_merge($formattedItem, [
                        'genre' => $item['genre'] ?? '',
                        'description' => $item['description'] ?? '',
                        'cover_image' => $item['cover_image'] ?? '',
                        'view_count' => intval($item['view_count'] ?? 0),
                        'favorite_count' => intval($item['favorite_count'] ?? 0),
                        'rating' => floatval($item['rating'] ?? 0),
                        'rating_count' => intval($item['rating_count'] ?? 0),
                        'creator' => [
                            'id' => $item['user_id'],
                            'username' => $item['username'] ?? '',
                            'nickname' => $item['nickname'] ?? ''
                        ]
                    ]);
                    break;
                    
                case 'music':
                    $formattedItem = array_merge($formattedItem, [
                        'genre' => $item['genre'] ?? '',
                        'description' => $item['description'] ?? '',
                        'cover_image' => $item['cover_image'] ?? '',
                        'play_count' => intval($item['play_count'] ?? 0),
                        'download_count' => intval($item['download_count'] ?? 0),
                        'favorite_count' => intval($item['favorite_count'] ?? 0),
                        'rating' => floatval($item['rating'] ?? 0),
                        'rating_count' => intval($item['rating_count'] ?? 0),
                        'artist' => [
                            'id' => $item['user_id'],
                            'username' => $item['username'] ?? '',
                            'nickname' => $item['nickname'] ?? ''
                        ]
                    ]);
                    break;
                    
                case 'creator':
                    $formattedItem = array_merge($formattedItem, [
                        'username' => $item['username'] ?? '',
                        'nickname' => $item['nickname'] ?? '',
                        'avatar' => $item['avatar'] ?? '',
                        'novel_count' => intval($item['novel_count'] ?? 0),
                        'anime_count' => intval($item['anime_count'] ?? 0),
                        'music_count' => intval($item['music_count'] ?? 0),
                        'total_views' => intval($item['total_views'] ?? 0),
                        'total_favorites' => intval($item['total_favorites'] ?? 0),
                        'avg_novel_rating' => floatval($item['avg_novel_rating'] ?? 0),
                        'avg_anime_rating' => floatval($item['avg_anime_rating'] ?? 0),
                        'avg_music_rating' => floatval($item['avg_music_rating'] ?? 0)
                    ]);
                    break;
                    
                case 'invitation':
                    $formattedItem = array_merge($formattedItem, [
                        'username' => $item['username'] ?? '',
                        'nickname' => $item['nickname'] ?? '',
                        'avatar' => $item['avatar'] ?? '',
                        'invitation_count' => intval($item['invitation_count'] ?? 0),
                        'total_recharge' => floatval($item['total_recharge'] ?? 0),
                        'total_reward' => floatval($item['total_reward'] ?? 0)
                    ]);
                    break;
            }
            
            $formatted[] = $formattedItem;
        }
        
        return $formatted;
    }
    
    /**
     * 刷新排行榜缓存
     */
    public function refreshCache()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // 验证权限（这里应该添加用户权限验证）
            // if (!$this->hasAdminPermission()) {
            //     throw new \Exception('无权限执行此操作');
            // }
            
            $type = $_GET['type'] ?? 'all';
            $period = $_GET['period'] ?? 'weekly';
            
            $validTypes = ['all', 'novel', 'anime', 'music', 'creator', 'invitation'];
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException('无效的排行榜类型');
            }
            
            $refreshed = [];
            
            if ($type === 'all') {
                // 刷新所有类型的排行榜
                $types = ['novel', 'anime', 'music', 'creator', 'invitation'];
                $rankingTypes = ['hot', 'new', 'favorite'];
                
                foreach ($types as $t) {
                    foreach ($rankingTypes as $rt) {
                        RankingCache::getOrGenerate($t, $period, $rt, 10);
                        $refreshed[] = "{$t}_{$rt}";
                    }
                }
            } else {
                // 刷新指定类型的排行榜
                $rankingTypes = ['hot', 'new', 'favorite'];
                foreach ($rankingTypes as $rt) {
                    RankingCache::getOrGenerate($type, $period, $rt, 10);
                    $refreshed[] = "{$type}_{$rt}";
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'refreshed' => $refreshed,
                    'period' => $period
                ],
                'message' => '排行榜缓存刷新成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => '刷新排行榜缓存失败'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * 获取排行榜统计信息
     */
    public function getStats()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $cacheStats = RankingCache::getCacheStats();
            
            // 获取各类型的基本统计
            $novelStats = Novel::getStats();
            $animeStats = Anime::getStats();
            $musicStats = Music::getStats();
            $invitationStats = UserInvitation::getGlobalStats();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'cache_stats' => $cacheStats,
                    'novel_stats' => $novelStats,
                    'anime_stats' => $animeStats,
                    'music_stats' => $musicStats,
                    'invitation_stats' => $invitationStats
                ],
                'message' => '获取排行榜统计成功'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => '获取排行榜统计失败'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}