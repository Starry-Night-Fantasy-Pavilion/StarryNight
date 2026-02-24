<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫发布管理模型
 */
class AnimePublication
{
    /**
     * 创建发布记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_publications` 
                (project_id, video_composition_id, platform, platform_video_id, title, description, tags, thumbnail_url, visibility, scheduled_time, published_time, view_count, like_count, comment_count, share_count, revenue, platform_data, status, error_message) 
                VALUES (:project_id, :video_composition_id, :platform, :platform_video_id, :title, :description, :tags, :thumbnail_url, :visibility, :scheduled_time, :published_time, :view_count, :like_count, :comment_count, :share_count, :revenue, :platform_data, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':video_composition_id' => $data['video_composition_id'],
            ':platform' => $data['platform'],
            ':platform_video_id' => $data['platform_video_id'] ?? null,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':thumbnail_url' => $data['thumbnail_url'] ?? null,
            ':visibility' => $data['visibility'] ?? 'public',
            ':scheduled_time' => $data['scheduled_time'] ?? null,
            ':published_time' => $data['published_time'] ?? null,
            ':view_count' => $data['view_count'] ?? 0,
            ':like_count' => $data['like_count'] ?? 0,
            ':comment_count' => $data['comment_count'] ?? 0,
            ':share_count' => $data['share_count'] ?? 0,
            ':revenue' => $data['revenue'] ?? 0.00,
            ':platform_data' => isset($data['platform_data']) ? json_encode($data['platform_data']) : null,
            ':status' => $data['status'] ?? 'scheduled',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取发布记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT p.*, pr.title as project_title, vc.video_name 
                FROM `{$prefix}anime_publications` p 
                LEFT JOIN `{$prefix}anime_projects` pr ON p.project_id = pr.id 
                LEFT JOIN `{$prefix}anime_video_compositions` vc ON p.video_composition_id = vc.id 
                WHERE p.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['platform_data']) {
            $result['platform_data'] = json_decode($result['platform_data'], true);
        }
        
        return $result;
    }

    /**
     * 获取项目的发布记录列表
     *
     * @param int $projectId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getByProject(int $projectId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["p.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['platform'])) {
            $where[] = "p.platform = :platform";
            $params[':platform'] = $filters['platform'];
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql = "SELECT p.*, vc.video_name 
                FROM `{$prefix}anime_publications` p 
                LEFT JOIN `{$prefix}anime_video_compositions` vc ON p.video_composition_id = vc.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($publications as &$publication) {
            if ($publication['platform_data']) {
                $publication['platform_data'] = json_decode($publication['platform_data'], true);
            }
        }

        return $publications;
    }

    /**
     * 更新发布记录
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
            'platform_video_id', 'title', 'description', 'tags', 'thumbnail_url',
            'visibility', 'published_time', 'view_count', 'like_count',
            'comment_count', 'share_count', 'revenue', 'platform_data', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if ($field === 'platform_data') {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_publications` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除发布记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_publications` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 执行智能内容审核
     *
     * @param int $videoCompositionId
     * @return array
     */
    public static function performContentReview(int $videoCompositionId): array
    {
        $videoComposition = AnimeVideoComposition::getById($videoCompositionId);
        if (!$videoComposition) {
            return [
                'success' => false,
                'error' => '视频不存在',
                'review_result' => null
            ];
        }

        // 模拟AI审核过程
        $reviewResult = self::simulateAIReview($videoComposition);
        
        // 更新视频的审核状态
        AnimeVideoComposition::update($videoCompositionId, [
            'content_rating' => $reviewResult['content_rating'],
            'copyright_check' => json_encode($reviewResult['copyright_check'])
        ]);

        return [
            'success' => true,
            'review_result' => $reviewResult
        ];
    }

    /**
     * 模拟AI审核过程
     *
     * @param array $videoComposition
     * @return array
     */
    private static function simulateAIReview(array $videoComposition): array
    {
        // 内容分级检查
        $contentRating = self::analyzeContentRating($videoComposition);
        
        // 版权检查
        $copyrightCheck = self::performCopyrightAnalysis($videoComposition);
        
        // 敏感内容检查
        $sensitiveContentCheck = self::checkSensitiveContent($videoComposition);
        
        // 质量评估
        $qualityAssessment = self::assessVideoQuality($videoComposition);
        
        // 综合评分
        $overallScore = ($contentRating['score'] + $copyrightCheck['score'] + 
                         $sensitiveContentCheck['score'] + $qualityAssessment['score']) / 4;
        
        return [
            'content_rating' => $contentRating['rating'],
            'copyright_check' => $copyrightCheck,
            'sensitive_content' => $sensitiveContentCheck,
            'quality_assessment' => $qualityAssessment,
            'overall_score' => $overallScore,
            'recommendations' => self::generateReviewRecommendations($contentRating, $copyrightCheck, $sensitiveContentCheck, $qualityAssessment),
            'passed' => $overallScore >= 7.0,
            'review_time' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 分析内容分级
     *
     * @param array $videoComposition
     * @return array
     */
    private static function analyzeContentRating(array $videoComposition): array
    {
        $title = $videoComposition['title'] ?? '';
        $description = $videoComposition['description'] ?? '';
        
        // 简单的关键词检查
        $adultKeywords = ['暴力', '血腥', '恐怖', '成人', '色情'];
        $teenKeywords = ['恋爱', '青春', '校园', '友情'];
        
        $hasAdultContent = false;
        $hasTeenContent = false;
        
        foreach ($adultKeywords as $keyword) {
            if (strpos($title . $description, $keyword) !== false) {
                $hasAdultContent = true;
                break;
            }
        }
        
        foreach ($teenKeywords as $keyword) {
            if (strpos($title . $description, $keyword) !== false) {
                $hasTeenContent = true;
                break;
            }
        }
        
        if ($hasAdultContent) {
            return ['rating' => 'R', 'score' => 6.0, 'reason' => '包含成人内容'];
        } elseif ($hasTeenContent) {
            return ['rating' => 'PG-13', 'score' => 8.0, 'reason' => '适合青少年观看'];
        } else {
            return ['rating' => 'G', 'score' => 9.5, 'reason' => '全年龄段适宜'];
        }
    }

    /**
     * 执行版权分析
     *
     * @param array $videoComposition
     * @return array
     */
    private static function performCopyrightAnalysis(array $videoComposition): array
    {
        // 模拟版权检查
        $issues = [];
        $score = 10.0;
        
        // 检查是否有明显的版权问题
        if (strpos($videoComposition['title'] ?? '', '版权') !== false) {
            $issues[] = '标题包含版权相关词汇';
            $score -= 2.0;
        }
        
        // 检查描述中的问题
        $description = $videoComposition['description'] ?? '';
        if (strlen($description) < 10) {
            $issues[] = '描述过短，可能存在版权风险';
            $score -= 1.0;
        }
        
        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'score' => max($score, 0),
            'issues' => $issues,
            'recommendations' => empty($issues) ? [] : ['建议添加原创声明', '检查素材来源']
        ];
    }

    /**
     * 检查敏感内容
     *
     * @param array $videoComposition
     * @return array
     */
    private static function checkSensitiveContent(array $videoComposition): array
    {
        $sensitiveKeywords = ['政治', '宗教', '种族歧视', '仇恨', '极端'];
        $content = ($videoComposition['title'] ?? '') . ' ' . ($videoComposition['description'] ?? '');
        
        $foundKeywords = [];
        foreach ($sensitiveKeywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }
        
        if (empty($foundKeywords)) {
            return ['status' => 'passed', 'score' => 10.0, 'issues' => []];
        } else {
            return [
                'status' => 'warning',
                'score' => 6.0,
                'issues' => $foundKeywords,
                'warning' => '包含敏感内容，需要谨慎处理'
            ];
        }
    }

    /**
     * 评估视频质量
     *
     * @param array $videoComposition
     * @return array
     */
    private static function assessVideoQuality(array $videoComposition): array
    {
        $score = 8.0; // 基础分数
        $issues = [];
        
        // 检查分辨率
        $resolution = $videoComposition['resolution'] ?? '1920x1080';
        if ($resolution === '1920x1080') {
            $score += 1.0;
        } elseif ($resolution === '3840x2160') {
            $score += 2.0;
        } else {
            $issues[] = '分辨率较低，建议提升';
            $score -= 1.0;
        }
        
        // 检查时长
        $duration = $videoComposition['total_duration'] ?? 0;
        if ($duration < 60) {
            $issues[] = '视频过短，可能影响观看体验';
            $score -= 0.5;
        } elseif ($duration > 7200) { // 超过2小时
            $issues[] = '视频过长，可能影响完播率';
            $score -= 0.5;
        }
        
        return [
            'score' => max(min($score, 10.0), 0),
            'issues' => $issues,
            'quality_level' => $score >= 9.0 ? 'high' : ($score >= 7.0 ? 'medium' : 'low')
        ];
    }

    /**
     * 生成审核建议
     *
     * @param array $contentRating
     * @param array $copyrightCheck
     * @param array $sensitiveContentCheck
     * @param array $qualityAssessment
     * @return array
     */
    private static function generateReviewRecommendations(array $contentRating, array $copyrightCheck, array $sensitiveContentCheck, array $qualityAssessment): array
    {
        $recommendations = [];
        
        if ($contentRating['score'] < 8.0) {
            $recommendations[] = "内容分级建议：{$contentRating['reason']}";
        }
        
        if ($copyrightCheck['status'] === 'warning') {
            $recommendations = array_merge($recommendations, $copyrightCheck['recommendations']);
        }
        
        if ($sensitiveContentCheck['status'] === 'warning') {
            $recommendations[] = "敏感内容警告：{$sensitiveContentCheck['warning']}";
        }
        
        if (!empty($qualityAssessment['issues'])) {
            $recommendations = array_merge($recommendations, $qualityAssessment['issues']);
        }
        
        return $recommendations;
    }

    /**
     * 发布到指定平台
     *
     * @param int $videoCompositionId
     * @param string $platform
     * @param array $publishOptions
     * @return array
     */
    public static function publishToPlatform(int $videoCompositionId, string $platform, array $publishOptions = []): array
    {
        // 首先进行内容审核
        $reviewResult = self::performContentReview($videoCompositionId);
        if (!$reviewResult['success'] || !$reviewResult['review_result']['passed']) {
            return [
                'success' => false,
                'error' => '内容审核未通过',
                'review_result' => $reviewResult['review_result']
            ];
        }

        $videoComposition = AnimeVideoComposition::getById($videoCompositionId);
        if (!$videoComposition) {
            return [
                'success' => false,
                'error' => '视频不存在'
            ];
        }

        // 模拟发布到不同平台
        $publishResult = self::simulatePlatformPublish($videoComposition, $platform, $publishOptions);
        
        // 创建发布记录
        $publicationData = [
            'project_id' => $videoComposition['project_id'],
            'video_composition_id' => $videoCompositionId,
            'platform' => $platform,
            'title' => $publishOptions['title'] ?? $videoComposition['video_name'],
            'description' => $publishOptions['description'] ?? $videoComposition['description'],
            'tags' => $publishOptions['tags'] ?? null,
            'thumbnail_url' => $publishOptions['thumbnail_url'] ?? $videoComposition['thumbnail_url'],
            'visibility' => $publishOptions['visibility'] ?? 'public',
            'status' => $publishResult['success'] ? 'published' : 'failed',
            'published_time' => $publishResult['success'] ? date('Y-m-d H:i:s') : null,
            'platform_data' => $publishResult['platform_data'] ?? null
        ];

        if (!$publishResult['success']) {
            $publicationData['error_message'] = $publishResult['error'];
        }

        $publicationId = self::create($publicationData);
        
        return [
            'success' => $publishResult['success'],
            'publication_id' => $publicationId,
            'platform_video_id' => $publishResult['platform_video_id'] ?? null,
            'error' => $publishResult['error'] ?? null
        ];
    }

    /**
     * 模拟平台发布
     *
     * @param array $videoComposition
     * @param string $platform
     * @param array $publishOptions
     * @return array
     */
    private static function simulatePlatformPublish(array $videoComposition, string $platform, array $publishOptions): array
    {
        $platformConfigs = [
            'bilibili' => [
                'api_endpoint' => 'https://api.bilibili.com/x/vu/video/add',
                'max_duration' => 7200,
                'supported_formats' => ['mp4', 'flv'],
                'requires_review' => true
            ],
            'youtube' => [
                'api_endpoint' => 'https://www.googleapis.com/upload/youtube/v3/videos',
                'max_duration' => 43200, // 12小时
                'supported_formats' => ['mp4', 'mov', 'avi'],
                'requires_review' => false
            ],
            'douyin' => [
                'api_endpoint' => 'https://open.douyin.com/api/video/upload',
                'max_duration' => 900, // 15分钟
                'supported_formats' => ['mp4'],
                'requires_review' => true
            ]
        ];

        $config = $platformConfigs[$platform] ?? null;
        if (!$config) {
            return [
                'success' => false,
                'error' => '不支持的平台'
            ];
        }

        // 检查视频格式
        $videoFormat = $videoComposition['video_format'] ?? 'mp4';
        if (!in_array($videoFormat, $config['supported_formats'])) {
            return [
                'success' => false,
                'error' => "平台不支持 {$videoFormat} 格式"
            ];
        }

        // 检查视频时长
        $duration = $videoComposition['total_duration'] ?? 0;
        if ($duration > $config['max_duration']) {
            return [
                'success' => false,
                'error' => "视频时长超过平台限制（最大 {$config['max_duration']} 秒）"
            ];
        }

        // 模拟成功发布
        return [
            'success' => true,
            'platform_video_id' => 'platform_' . time() . '_' . rand(1000, 9999),
            'platform_data' => [
                'upload_time' => date('Y-m-d H:i:s'),
                'file_size' => $videoComposition['file_size'],
                'resolution' => $videoComposition['resolution'],
                'duration' => $duration,
                'platform_specific_data' => [
                    'category' => $publishOptions['category'] ?? 'animation',
                    'language' => $publishOptions['language'] ?? 'zh-CN',
                    'allow_comments' => $publishOptions['allow_comments'] ?? true,
                    'allow_download' => $publishOptions['allow_download'] ?? false
                ]
            ]
        ];
    }

    /**
     * 批量发布到多个平台
     *
     * @param int $videoCompositionId
     * @param array $platforms
     * @param array $publishOptions
     * @return array
     */
    public static function batchPublish(int $videoCompositionId, array $platforms, array $publishOptions = []): array
    {
        $results = [];
        $successCount = 0;
        
        foreach ($platforms as $platform) {
            $result = self::publishToPlatform($videoCompositionId, $platform, $publishOptions);
            $results[$platform] = $result;
            
            if ($result['success']) {
                $successCount++;
            }
        }
        
        return [
            'success' => $successCount > 0,
            'total_platforms' => count($platforms),
            'success_count' => $successCount,
            'results' => $results
        ];
    }

    /**
     * 获取发布统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getPublicationStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_publications,
                    COUNT(CASE WHEN platform = 'bilibili' THEN 1 END) as bilibili_count,
                    COUNT(CASE WHEN platform = 'youtube' THEN 1 END) as youtube_count,
                    COUNT(CASE WHEN platform = 'douyin' THEN 1 END) as douyin_count,
                    COUNT(CASE WHEN platform = 'weibo' THEN 1 END) as weibo_count,
                    SUM(view_count) as total_views,
                    SUM(like_count) as total_likes,
                    SUM(comment_count) as total_comments,
                    SUM(share_count) as total_shares,
                    SUM(revenue) as total_revenue,
                    COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                    COUNT(CASE WHEN published_time >= CURDATE() - INTERVAL 7 DAY THEN 1 END) as recent_publications
                FROM `{$prefix}anime_publications` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 同步平台数据
     *
     * @param int $publicationId
     * @return array
     */
    public static function syncPlatformData(int $publicationId): array
    {
        $publication = self::getById($publicationId);
        if (!$publication) {
            return [
                'success' => false,
                'error' => '发布记录不存在'
            ];
        }

        // 模拟从平台同步数据
        $syncData = self::simulatePlatformSync($publication);
        
        // 更新数据库中的数据
        self::update($publicationId, [
            'view_count' => $syncData['view_count'],
            'like_count' => $syncData['like_count'],
            'comment_count' => $syncData['comment_count'],
            'share_count' => $syncData['share_count'],
            'revenue' => $syncData['revenue'],
            'platform_data' => json_encode($syncData['platform_data'])
        ]);

        return [
            'success' => true,
            'sync_data' => $syncData,
            'updated_fields' => ['view_count', 'like_count', 'comment_count', 'share_count', 'revenue', 'platform_data']
        ];
    }

    /**
     * 模拟平台数据同步
     *
     * @param array $publication
     * @return array
     */
    private static function simulatePlatformSync(array $publication): array
    {
        $baseViews = $publication['view_count'] ?? 0;
        $baseLikes = $publication['like_count'] ?? 0;
        $baseComments = $publication['comment_count'] ?? 0;
        $baseShares = $publication['share_count'] ?? 0;
        
        // 模拟数据增长
        $newViews = $baseViews + rand(100, 1000);
        $newLikes = $baseLikes + rand(10, 100);
        $newComments = $baseComments + rand(5, 50);
        $newShares = $baseShares + rand(2, 20);
        
        // 计算收益（模拟）
        $revenue = ($newViews * 0.001) + ($newLikes * 0.01) + ($newComments * 0.02);
        
        return [
            'view_count' => $newViews,
            'like_count' => $newLikes,
            'comment_count' => $newComments,
            'share_count' => $newShares,
            'revenue' => round($revenue, 2),
            'platform_data' => array_merge(
                json_decode($publication['platform_data'] ?? '{}', true),
                [
                    'last_sync' => date('Y-m-d H:i:s'),
                    'engagement_rate' => round(($newLikes + $newComments) / $newViews * 100, 2),
                    'trending_score' => rand(1, 100)
                ]
            )
        ];
    }
}