<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫动画生成模型
 */
class AnimeAnimation
{
    /**
     * 创建动画生成记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_animations` 
                (project_id, storyboard_id, scene_id, character_id, animation_type, animation_name, description, duration, frame_rate, resolution, key_frames, animation_data, motion_capture_data, physics_simulation, render_settings, output_format, file_size, file_url, preview_url, render_time, render_cost, quality_score, status, error_message) 
                VALUES (:project_id, :storyboard_id, :scene_id, :character_id, :animation_type, :animation_name, :description, :duration, :frame_rate, :resolution, :key_frames, :animation_data, :motion_capture_data, :physics_simulation, :render_settings, :output_format, :file_size, :file_url, :preview_url, :render_time, :render_cost, :quality_score, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':storyboard_id' => $data['storyboard_id'] ?? null,
            ':scene_id' => $data['scene_id'] ?? null,
            ':character_id' => $data['character_id'] ?? null,
            ':animation_type' => $data['animation_type'] ?? 'character_animation',
            ':animation_name' => $data['animation_name'],
            ':description' => $data['description'] ?? null,
            ':duration' => $data['duration'] ?? 0.00,
            ':frame_rate' => $data['frame_rate'] ?? 24,
            ':resolution' => $data['resolution'] ?? '1920x1080',
            ':key_frames' => isset($data['key_frames']) ? json_encode($data['key_frames']) : null,
            ':animation_data' => isset($data['animation_data']) ? json_encode($data['animation_data']) : null,
            ':motion_capture_data' => isset($data['motion_capture_data']) ? json_encode($data['motion_capture_data']) : null,
            ':physics_simulation' => isset($data['physics_simulation']) ? json_encode($data['physics_simulation']) : null,
            ':render_settings' => isset($data['render_settings']) ? json_encode($data['render_settings']) : null,
            ':output_format' => $data['output_format'] ?? 'mp4',
            ':file_size' => $data['file_size'] ?? 0,
            ':file_url' => $data['file_url'] ?? null,
            ':preview_url' => $data['preview_url'] ?? null,
            ':render_time' => $data['render_time'] ?? 0,
            ':render_cost' => $data['render_cost'] ?? 0.0000,
            ':quality_score' => $data['quality_score'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取动画生成记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT a.*, ap.title as project_title, sb.description as storyboard_description, 
                       s.scene_name, c.name as character_name 
                FROM `{$prefix}anime_animations` a 
                LEFT JOIN `{$prefix}anime_projects` ap ON a.project_id = ap.id 
                LEFT JOIN `{$prefix}anime_storyboards` sb ON a.storyboard_id = sb.id 
                LEFT JOIN `{$prefix}anime_scenes` s ON a.scene_id = s.id 
                LEFT JOIN `{$prefix}anime_characters` c ON a.character_id = c.id 
                WHERE a.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // 解析JSON字段
            $jsonFields = ['key_frames', 'animation_data', 'motion_capture_data', 'physics_simulation', 'render_settings'];
            foreach ($jsonFields as $field) {
                if ($result[$field]) {
                    $result[$field] = json_decode($result[$field], true);
                }
            }
        }
        
        return $result;
    }

    /**
     * 获取项目的动画生成记录列表
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

        $where = ["a.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['animation_type'])) {
            $where[] = "a.animation_type = :animation_type";
            $params[':animation_type'] = $filters['animation_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['character_id'])) {
            $where[] = "a.character_id = :character_id";
            $params[':character_id'] = $filters['character_id'];
        }

        $sql = "SELECT a.*, c.name as character_name, s.scene_name 
                FROM `{$prefix}anime_animations` a 
                LEFT JOIN `{$prefix}anime_characters` c ON a.character_id = c.id 
                LEFT JOIN `{$prefix}anime_scenes` s ON a.scene_id = s.id 
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

        $animations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($animations as &$animation) {
            $jsonFields = ['key_frames', 'animation_data', 'motion_capture_data', 'physics_simulation', 'render_settings'];
            foreach ($jsonFields as $field) {
                if ($animation[$field]) {
                    $animation[$field] = json_decode($animation[$field], true);
                }
            }
        }

        return $animations;
    }

    /**
     * 更新动画生成记录
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
            'animation_name', 'description', 'duration', 'frame_rate', 'resolution',
            'key_frames', 'animation_data', 'motion_capture_data', 'physics_simulation',
            'render_settings', 'output_format', 'file_size', 'file_url', 'preview_url',
            'render_time', 'render_cost', 'quality_score', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if (in_array($field, ['key_frames', 'animation_data', 'motion_capture_data', 'physics_simulation', 'render_settings'])) {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_animations` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除动画生成记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_animations` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI生成动画关键帧
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildAnimationPrompt($params);
        
        // 这里应该调用AI服务生成动画关键帧
        // 暂时返回模拟数据
        return [
            'animation_name' => $params['animation_name'] ?? '未命名动画',
            'animation_type' => $params['animation_type'] ?? 'character_animation',
            'description' => '基于AI生成的动画关键帧数据',
            'duration' => $params['duration'] ?? 5.0,
            'frame_rate' => $params['frame_rate'] ?? 24,
            'resolution' => $params['resolution'] ?? '1920x1080',
            'key_frames' => self::generateMockKeyFrames($params),
            'animation_data' => [
                'interpolation_method' => 'bezier',
                'easing' => 'ease-in-out',
                'loop' => false
            ],
            'render_settings' => [
                'quality' => 'high',
                'anti_aliasing' => true,
                'motion_blur' => true,
                'depth_of_field' => false
            ]
        ];
    }

    /**
     * 构建动画生成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildAnimationPrompt(array $params): string
    {
        $prompt = "动漫动画生成提示词：\n";
        $prompt .= "你是一位动画师。请根据以下分镜描述和角色模型生成动画关键帧：\n";
        
        if (!empty($params['storyboard_description'])) {
            $prompt .= "【分镜描述】：{$params['storyboard_description']}\n";
        }
        
        if (!empty($params['character_model_reference'])) {
            $prompt .= "【角色模型】：{$params['character_model_reference']}\n";
        }
        
        if (!empty($params['action_requirements'])) {
            $prompt .= "【动作要求】：{$params['action_requirements']}\n";
        }
        
        if (!empty($params['animation_style'])) {
            $prompt .= "【风格要求】：{$params['animation_style']}\n";
        }

        $prompt .= "\n请生成包含以下内容的动画关键帧描述：
1. 关键帧时间点
2. 角色姿态
   - 身体姿态
   - 四肢动作
   - 表情变化
   
3. 场景互动
   - 角色与场景元素的互动
   
4. 运镜变化
   - 镜头位置、角度、焦距变化

要求：动作流畅、表情生动、符合物理规律。";

        return $prompt;
    }

    /**
     * 生成模拟关键帧数据
     *
     * @param array $params
     * @return array
     */
    private static function generateMockKeyFrames(array $params): array
    {
        $duration = $params['duration'] ?? 5.0;
        $frameRate = $params['frame_rate'] ?? 24;
        $totalFrames = intval($duration * $frameRate);
        
        $keyFrames = [];
        
        // 起始帧
        $keyFrames[] = [
            'frame' => 0,
            'time' => 0.0,
            'position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
            'expression' => 'neutral',
            'pose' => 'standing'
        ];
        
        // 中间帧
        $midFrame = intval($totalFrames / 2);
        $keyFrames[] = [
            'frame' => $midFrame,
            'time' => round($midFrame / $frameRate, 2),
            'position' => ['x' => 50, 'y' => 10, 'z' => 0],
            'rotation' => ['x' => 5, 'y' => 15, 'z' => 0],
            'scale' => ['x' => 1.1, 'y' => 1.1, 'z' => 1],
            'expression' => 'smile',
            'pose' => 'walking'
        ];
        
        // 结束帧
        $keyFrames[] = [
            'frame' => $totalFrames,
            'time' => $duration,
            'position' => ['x' => 100, 'y' => 0, 'z' => 0],
            'rotation' => ['x' => 0, 'y' => 30, 'z' => 0],
            'scale' => ['x' => 1, 'y' => 1, 'z' => 1],
            'expression' => 'happy',
            'pose' => 'standing'
        ];
        
        return $keyFrames;
    }

    /**
     * 获取动画统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getAnimationStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_animations,
                    COUNT(CASE WHEN animation_type = 'character_animation' THEN 1 END) as character_animations,
                    COUNT(CASE WHEN animation_type = 'scene_animation' THEN 1 END) as scene_animations,
                    COUNT(CASE WHEN animation_type = 'effect_animation' THEN 1 END) as effect_animations,
                    COUNT(CASE WHEN animation_type = 'transition_animation' THEN 1 END) as transition_animations,
                    SUM(duration) as total_duration,
                    SUM(render_time) as total_render_time,
                    SUM(render_cost) as total_render_cost,
                    AVG(quality_score) as avg_quality_score,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_animations,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_animations,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_animations
                FROM `{$prefix}anime_animations` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新动画状态
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

        $sql = "UPDATE `{$prefix}anime_animations` SET status = :status";
        $params = [
            ':status' => $status,
            ':id' => $id
        ];

        if ($errorMessage) {
            $sql .= ", error_message = :error_message";
            $params[':error_message'] = $errorMessage;
        }

        if ($status === 'completed') {
            $sql .= ", completed_at = CURRENT_TIMESTAMP";
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 获取正在处理的动画列表
     *
     * @param int $projectId
     * @return array
     */
    public static function getProcessingAnimations(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_animations` 
                WHERE project_id = :project_id AND status = 'processing' 
                ORDER BY created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 批量更新动画状态
     *
     * @param array $ids
     * @param string $status
     * @return bool
     */
    public static function batchUpdateStatus(array $ids, string $status): bool
    {
        if (empty($ids)) {
            return false;
        }

        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $sql = "UPDATE `{$prefix}anime_animations` SET status = ? WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $ids);
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 获取高质量动画列表
     *
     * @param int $projectId
     * @param float $minQualityScore
     * @return array
     */
    public static function getHighQualityAnimations(int $projectId, float $minQualityScore = 8.0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_animations` 
                WHERE project_id = :project_id AND status = 'completed' 
                AND quality_score >= :min_quality_score 
                ORDER BY quality_score DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':min_quality_score' => $minQualityScore
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}