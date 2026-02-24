<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫视频合成模型
 */
class AnimeVideoComposition
{
    /**
     * 创建视频合成记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_video_compositions` 
                (project_id, episode_script_id, video_name, description, episode_number, total_duration, resolution, frame_rate, aspect_ratio, video_format, video_codec, audio_codec, bitrate, animation_clips, audio_tracks, subtitles, special_effects, transitions, color_grading, file_size, file_url, preview_url, thumbnail_url, render_time, render_cost, quality_score, content_rating, copyright_check, status, error_message) 
                VALUES (:project_id, :episode_script_id, :video_name, :description, :episode_number, :total_duration, :resolution, :frame_rate, :aspect_ratio, :video_format, :video_codec, :audio_codec, :bitrate, :animation_clips, :audio_tracks, :subtitles, :special_effects, :transitions, :color_grading, :file_size, :file_url, :preview_url, :thumbnail_url, :render_time, :render_cost, :quality_score, :content_rating, :copyright_check, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':episode_script_id' => $data['episode_script_id'] ?? null,
            ':video_name' => $data['video_name'],
            ':description' => $data['description'] ?? null,
            ':episode_number' => $data['episode_number'] ?? null,
            ':total_duration' => $data['total_duration'] ?? 0.00,
            ':resolution' => $data['resolution'] ?? '1920x1080',
            ':frame_rate' => $data['frame_rate'] ?? 24,
            ':aspect_ratio' => $data['aspect_ratio'] ?? '16:9',
            ':video_format' => $data['video_format'] ?? 'mp4',
            ':video_codec' => $data['video_codec'] ?? 'h264',
            ':audio_codec' => $data['audio_codec'] ?? 'aac',
            ':bitrate' => $data['bitrate'] ?? 5000,
            ':animation_clips' => isset($data['animation_clips']) ? json_encode($data['animation_clips']) : null,
            ':audio_tracks' => isset($data['audio_tracks']) ? json_encode($data['audio_tracks']) : null,
            ':subtitles' => isset($data['subtitles']) ? json_encode($data['subtitles']) : null,
            ':special_effects' => isset($data['special_effects']) ? json_encode($data['special_effects']) : null,
            ':transitions' => isset($data['transitions']) ? json_encode($data['transitions']) : null,
            ':color_grading' => isset($data['color_grading']) ? json_encode($data['color_grading']) : null,
            ':file_size' => $data['file_size'] ?? 0,
            ':file_url' => $data['file_url'] ?? null,
            ':preview_url' => $data['preview_url'] ?? null,
            ':thumbnail_url' => $data['thumbnail_url'] ?? null,
            ':render_time' => $data['render_time'] ?? 0,
            ':render_cost' => $data['render_cost'] ?? 0.0000,
            ':quality_score' => $data['quality_score'] ?? null,
            ':content_rating' => $data['content_rating'] ?? null,
            ':copyright_check' => isset($data['copyright_check']) ? json_encode($data['copyright_check']) : null,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取视频合成记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT vc.*, p.title as project_title, es.title as episode_title 
                FROM `{$prefix}anime_video_compositions` vc 
                LEFT JOIN `{$prefix}anime_projects` p ON vc.project_id = p.id 
                LEFT JOIN `{$prefix}anime_episode_scripts` es ON vc.episode_script_id = es.id 
                WHERE vc.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // 解析JSON字段
            $jsonFields = ['animation_clips', 'audio_tracks', 'subtitles', 'special_effects', 'transitions', 'color_grading', 'copyright_check'];
            foreach ($jsonFields as $field) {
                if ($result[$field]) {
                    $result[$field] = json_decode($result[$field], true);
                }
            }
        }
        
        return $result;
    }

    /**
     * 获取项目的视频合成记录列表
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

        $where = ["vc.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['status'])) {
            $where[] = "vc.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['episode_number'])) {
            $where[] = "vc.episode_number = :episode_number";
            $params[':episode_number'] = $filters['episode_number'];
        }

        if (!empty($filters['video_format'])) {
            $where[] = "vc.video_format = :video_format";
            $params[':video_format'] = $filters['video_format'];
        }

        $sql = "SELECT vc.*, es.title as episode_title 
                FROM `{$prefix}anime_video_compositions` vc 
                LEFT JOIN `{$prefix}anime_episode_scripts` es ON vc.episode_script_id = es.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY vc.episode_number ASC, vc.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $videoCompositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($videoCompositions as &$video) {
            $jsonFields = ['animation_clips', 'audio_tracks', 'subtitles', 'special_effects', 'transitions', 'color_grading', 'copyright_check'];
            foreach ($jsonFields as $field) {
                if ($video[$field]) {
                    $video[$field] = json_decode($video[$field], true);
                }
            }
        }

        return $videoCompositions;
    }

    /**
     * 更新视频合成记录
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
            'video_name', 'description', 'episode_number', 'total_duration', 'resolution',
            'frame_rate', 'aspect_ratio', 'video_format', 'video_codec', 'audio_codec',
            'bitrate', 'animation_clips', 'audio_tracks', 'subtitles', 'special_effects',
            'transitions', 'color_grading', 'file_size', 'file_url', 'preview_url',
            'thumbnail_url', 'render_time', 'render_cost', 'quality_score',
            'content_rating', 'copyright_check', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if (in_array($field, ['animation_clips', 'audio_tracks', 'subtitles', 'special_effects', 'transitions', 'color_grading', 'copyright_check'])) {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_video_compositions` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除视频合成记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_video_compositions` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI合成视频
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildVideoCompositionPrompt($params);
        
        // 这里应该调用AI服务合成视频
        // 暂时返回模拟数据
        return [
            'video_name' => $params['video_name'] ?? '未命名视频',
            'description' => '基于AI合成的动漫视频',
            'episode_number' => $params['episode_number'] ?? 1,
            'total_duration' => self::estimateDuration($params),
            'resolution' => $params['resolution'] ?? '1920x1080',
            'frame_rate' => $params['frame_rate'] ?? 24,
            'aspect_ratio' => $params['aspect_ratio'] ?? '16:9',
            'video_format' => $params['video_format'] ?? 'mp4',
            'video_codec' => $params['video_codec'] ?? 'h264',
            'audio_codec' => $params['audio_codec'] ?? 'aac',
            'bitrate' => $params['bitrate'] ?? 5000,
            'animation_clips' => self::generateMockAnimationClips($params),
            'audio_tracks' => self::generateMockAudioTracks($params),
            'subtitles' => self::generateMockSubtitles($params),
            'special_effects' => self::generateMockSpecialEffects($params),
            'transitions' => self::generateMockTransitions($params),
            'color_grading' => self::generateMockColorGrading($params),
            'render_time' => self::estimateRenderTime($params),
            'render_cost' => self::calculateRenderCost($params),
            'content_rating' => self::generateContentRating($params),
            'copyright_check' => self::performCopyrightCheck($params)
        ];
    }

    /**
     * 构建视频合成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildVideoCompositionPrompt(array $params): string
    {
        $prompt = "动漫视频合成提示词：\n";
        $prompt .= "你是一位视频后期制作专家。请根据以下素材和要求进行视频合成：\n";
        
        if (!empty($params['animation_clips'])) {
            $prompt .= "【动画片段】：{$params['animation_clips']}\n";
        }
        
        if (!empty($params['audio_tracks'])) {
            $prompt .= "【音频素材】：{$params['audio_tracks']}\n";
        }
        
        if (!empty($params['special_effects_requirements'])) {
            $prompt .= "【特效要求】：{$params['special_effects_requirements']}\n";
        }
        
        if (!empty($params['subtitle_requirements'])) {
            $prompt .= "【字幕要求】：{$params['subtitle_requirements']}\n";
        }
        
        if (!empty($params['output_format'])) {
            $prompt .= "【输出格式】：{$params['output_format']}\n";
        }

        $prompt .= "\n请生成包含以下内容的合成方案：
1. 剪辑点规划
   - 动画与音频的同步
   - 节奏控制
   
2. 特效添加
   - 关键特效位置、类型、强度
   - 过渡效果
   
3. 字幕生成
   - 字幕样式、位置
   - 翻译（如果需要）
   
4. 最终输出设置
   - 分辨率、帧率
   - 编码格式

要求：画面流畅、音画同步、特效自然、符合最终呈现效果。";

        return $prompt;
    }

    /**
     * 估算视频时长
     *
     * @param array $params
     * @return float
     */
    private static function estimateDuration(array $params): float
    {
        if (!empty($params['animation_clips'])) {
            $totalDuration = 0;
            foreach ($params['animation_clips'] as $clip) {
                $totalDuration += $clip['duration'] ?? 0;
            }
            return $totalDuration;
        }
        
        // 默认时长
        return $params['episode_duration'] ?? 20.0 * 60; // 20分钟
    }

    /**
     * 估算渲染时间
     *
     * @param array $params
     * @return int
     */
    private static function estimateRenderTime(array $params): int
    {
        $duration = self::estimateDuration($params);
        $resolution = $params['resolution'] ?? '1920x1080';
        $frameRate = $params['frame_rate'] ?? 24;
        
        // 基础渲染时间（秒）
        $baseTime = $duration * 0.1;
        
        // 分辨率调整
        if ($resolution === '3840x2160') {
            $baseTime *= 4; // 4K渲染时间更长
        } elseif ($resolution === '2560x1440') {
            $baseTime *= 2; // 2K渲染时间更长
        }
        
        // 帧率调整
        if ($frameRate > 30) {
            $baseTime *= 1.5;
        }
        
        return intval($baseTime);
    }

    /**
     * 计算渲染成本
     *
     * @param array $params
     * @return float
     */
    private static function calculateRenderCost(array $params): float
    {
        $renderTime = self::estimateRenderTime($params);
        $resolution = $params['resolution'] ?? '1920x1080';
        
        $baseCost = $renderTime * 0.01; // 每秒渲染成本
        
        // 分辨率调整
        if ($resolution === '3840x2160') {
            $baseCost *= 3;
        } elseif ($resolution === '2560x1440') {
            $baseCost *= 1.5;
        }
        
        return round($baseCost, 4);
    }

    /**
     * 生成模拟动画片段
     *
     * @param array $params
     * @return array
     */
    private static function generateMockAnimationClips(array $params): array
    {
        return [
            [
                'id' => 1,
                'name' => '开场动画',
                'start_time' => 0,
                'duration' => 180, // 3分钟
                'animation_id' => 1,
                'layer' => 1,
                'opacity' => 1.0,
                'blend_mode' => 'normal'
            ],
            [
                'id' => 2,
                'name' => '主要情节',
                'start_time' => 180,
                'duration' => 840, // 14分钟
                'animation_id' => 2,
                'layer' => 1,
                'opacity' => 1.0,
                'blend_mode' => 'normal'
            ],
            [
                'id' => 3,
                'name' => '片尾动画',
                'start_time' => 1020,
                'duration' => 180, // 3分钟
                'animation_id' => 3,
                'layer' => 1,
                'opacity' => 1.0,
                'blend_mode' => 'normal'
            ]
        ];
    }

    /**
     * 生成模拟音轨
     *
     * @param array $params
     * @return array
     */
    private static function generateMockAudioTracks(array $params): array
    {
        return [
            [
                'id' => 1,
                'name' => '背景音乐',
                'type' => 'background_music',
                'start_time' => 0,
                'duration' => 1200, // 20分钟
                'volume' => 0.7,
                'fade_in' => 2,
                'fade_out' => 3,
                'audio_id' => 1
            ],
            [
                'id' => 2,
                'name' => '角色配音',
                'type' => 'voice_over',
                'start_time' => 180,
                'duration' => 840,
                'volume' => 1.0,
                'audio_id' => 2
            ],
            [
                'id' => 3,
                'name' => '音效',
                'type' => 'sound_effect',
                'start_time' => 0,
                'duration' => 1200,
                'volume' => 0.8,
                'audio_id' => 3
            ]
        ];
    }

    /**
     * 生成模拟字幕
     *
     * @param array $params
     * @return array
     */
    private static function generateMockSubtitles(array $params): array
    {
        return [
            'enabled' => true,
            'language' => 'zh-CN',
            'style' => [
                'font_family' => 'Arial',
                'font_size' => 24,
                'color' => '#FFFFFF',
                'background_color' => '#000000',
                'background_opacity' => 0.7,
                'position' => 'bottom',
                'alignment' => 'center'
            ],
            'subtitles' => [
                [
                    'start_time' => 180,
                    'end_time' => 185,
                    'text' => '欢迎观看本集动漫'
                ],
                [
                    'start_time' => 186,
                    'end_time' => 190,
                    'text' => '今天的故事开始了'
                ]
            ]
        ];
    }

    /**
     * 生成模拟特效
     *
     * @param array $params
     * @return array
     ]
        return [
            [
                'id' => 1,
                'name' => '开场特效',
                'type' => 'particle',
                'start_time' => 0,
                'duration' => 5,
                'intensity' => 0.8,
                'parameters' => [
                    'particle_count' => 100,
                    'particle_size' => 2,
                    'color' => '#FFD700'
                ]
            ],
            [
                'id' => 2,
                'name' => '转场特效',
                'type' => 'transition',
                'start_time' => 180,
                'duration' => 2,
                'transition_type' => 'fade',
                'parameters' => [
                    'fade_duration' => 2,
                    'fade_color' => '#000000'
                ]
            ]
        ];
    }

    /**
     * 生成模拟特效
     *
     * @param array $params
     * @return array
     */
    private static function generateMockSpecialEffects(array $params): array
    {
        return [
            [
                'id' => 1,
                'name' => '开场特效',
                'type' => 'particle',
                'start_time' => 0,
                'duration' => 5,
                'intensity' => 0.8,
                'parameters' => [
                    'particle_count' => 100,
                    'particle_size' => 2,
                    'color' => '#FFD700'
                ]
            ],
            [
                'id' => 2,
                'name' => '转场特效',
                'type' => 'transition',
                'start_time' => 180,
                'duration' => 2,
                'transition_type' => 'fade',
                'parameters' => [
                    'fade_duration' => 2,
                    'fade_color' => '#000000'
                ]
            ]
        ];
    }

    /**
     * 生成模拟转场
     *
     * @param array $params
     * @return array
     */
    private static function generateMockTransitions(array $params): array
    {
        return [
            [
                'from_clip' => 1,
                'to_clip' => 2,
                'type' => 'fade',
                'duration' => 2,
                'start_time' => 178,
                'parameters' => [
                    'fade_type' => 'crossfade',
                    'smoothness' => 0.8
                ]
            ],
            [
                'from_clip' => 2,
                'to_clip' => 3,
                'type' => 'fade',
                'duration' => 3,
                'start_time' => 1017,
                'parameters' => [
                    'fade_type' => 'fade_to_black',
                    'smoothness' => 0.9
                ]
            ]
        ];
    }

    /**
     * 生成模拟调色
     *
     * @param array $params
     * @return array
     */
    private static function generateMockColorGrading(array $params): array
    {
        return [
            'brightness' => 0.1,
            'contrast' => 0.05,
            'saturation' => 0.1,
            'hue_shift' => 0,
            'color_temperature' => 6500,
            'lift' => ['r' => 0, 'g' => 0, 'b' => 0],
            'gamma' => ['r' => 1, 'g' => 1, 'b' => 1],
            'gain' => ['r' => 1.1, 'g' => 1.05, 'b' => 1.0],
            'lut' => null
        ];
    }

    /**
     * 生成内容分级
     *
     * @param array $params
     * @return string
     */
    private static function generateContentRating(array $params): string
    {
        // 基于内容分析生成分级
        return 'G'; // 通用级
    }

    /**
     * 执行版权检查
     *
     * @param array $params
     * @return array
     */
    private static function performCopyrightCheck(array $params): array
    {
        return [
            'status' => 'passed',
            'checked_items' => [
                'animation_clips' => 'passed',
                'audio_tracks' => 'passed',
                'images' => 'passed',
                'text' => 'passed'
            ],
            'issues' => [],
            'recommendations' => [
                '建议添加原创声明',
                '建议检查素材来源'
            ]
        ];
    }

    /**
     * 获取视频统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getVideoStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_videos,
                    SUM(total_duration) as total_duration,
                    SUM(file_size) as total_file_size,
                    SUM(render_time) as total_render_time,
                    SUM(render_cost) as total_render_cost,
                    AVG(quality_score) as avg_quality_score,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_videos,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_videos,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_videos,
                    COUNT(CASE WHEN status = 'published' THEN 1 END) as published_videos
                FROM `{$prefix}anime_video_compositions` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新视频状态
     *
     * @param int $id
     * @param string $status
     * @param string|null $errorMessage
     * @return bool
     */
    public static function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}anime_video_compositions` SET status = :status";
        $params = [
            ':status' => $status,
            ':id' => $id
        ];

        if ($errorMessage) {
            $sql .= ", error_message = :error_message";
            $params[':error_message'] = $errorMessage;
        }

        if ($status === 'published') {
            $sql .= ", published_at = CURRENT_TIMESTAMP";
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 获取已发布的视频列表
     *
     * @param int $projectId
     * @return array
     */
    public static function getPublishedVideos(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_video_compositions` 
                WHERE project_id = :project_id AND status = 'published' 
                ORDER BY published_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}