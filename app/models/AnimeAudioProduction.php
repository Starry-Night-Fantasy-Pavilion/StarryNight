<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫音频制作模型
 */
class AnimeAudioProduction
{
    /**
     * 创建音频制作记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_audio_productions` 
                (project_id, episode_script_id, animation_id, audio_type, audio_name, description, character_id, voice_actor, voice_style, language, duration, script_text, emotion_tone, volume_level, audio_settings, file_format, file_size, file_url, preview_url, generation_cost, quality_score, status, error_message) 
                VALUES (:project_id, :episode_script_id, :animation_id, :audio_type, :audio_name, :description, :character_id, :voice_actor, :voice_style, :language, :duration, :script_text, :emotion_tone, :volume_level, :audio_settings, :file_format, :file_size, :file_url, :preview_url, :generation_cost, :quality_score, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':episode_script_id' => $data['episode_script_id'] ?? null,
            ':animation_id' => $data['animation_id'] ?? null,
            ':audio_type' => $data['audio_type'] ?? 'voice_over',
            ':audio_name' => $data['audio_name'],
            ':description' => $data['description'] ?? null,
            ':character_id' => $data['character_id'] ?? null,
            ':voice_actor' => $data['voice_actor'] ?? null,
            ':voice_style' => $data['voice_style'] ?? null,
            ':language' => $data['language'] ?? 'zh-CN',
            ':duration' => $data['duration'] ?? 0.00,
            ':script_text' => $data['script_text'] ?? null,
            ':emotion_tone' => $data['emotion_tone'] ?? null,
            ':volume_level' => $data['volume_level'] ?? 1.00,
            ':audio_settings' => isset($data['audio_settings']) ? json_encode($data['audio_settings']) : null,
            ':file_format' => $data['file_format'] ?? 'mp3',
            ':file_size' => $data['file_size'] ?? 0,
            ':file_url' => $data['file_url'] ?? null,
            ':preview_url' => $data['preview_url'] ?? null,
            ':generation_cost' => $data['generation_cost'] ?? 0.0000,
            ':quality_score' => $data['quality_score'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取音频制作记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ap.*, p.title as project_title, es.title as episode_title, 
                       a.animation_name, c.name as character_name 
                FROM `{$prefix}anime_audio_productions` ap 
                LEFT JOIN `{$prefix}anime_projects` p ON ap.project_id = p.id 
                LEFT JOIN `{$prefix}anime_episode_scripts` es ON ap.episode_script_id = es.id 
                LEFT JOIN `{$prefix}anime_animations` a ON ap.animation_id = a.id 
                LEFT JOIN `{$prefix}anime_characters` c ON ap.character_id = c.id 
                WHERE ap.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['audio_settings']) {
            $result['audio_settings'] = json_decode($result['audio_settings'], true);
        }
        
        return $result;
    }

    /**
     * 获取项目的音频制作记录列表
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

        $where = ["ap.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['audio_type'])) {
            $where[] = "ap.audio_type = :audio_type";
            $params[':audio_type'] = $filters['audio_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "ap.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['character_id'])) {
            $where[] = "ap.character_id = :character_id";
            $params[':character_id'] = $filters['character_id'];
        }

        if (!empty($filters['episode_script_id'])) {
            $where[] = "ap.episode_script_id = :episode_script_id";
            $params[':episode_script_id'] = $filters['episode_script_id'];
        }

        $sql = "SELECT ap.*, c.name as character_name, es.title as episode_title 
                FROM `{$prefix}anime_audio_productions` ap 
                LEFT JOIN `{$prefix}anime_characters` c ON ap.character_id = c.id 
                LEFT JOIN `{$prefix}anime_episode_scripts` es ON ap.episode_script_id = es.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY ap.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $audioProductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($audioProductions as &$audio) {
            if ($audio['audio_settings']) {
                $audio['audio_settings'] = json_decode($audio['audio_settings'], true);
            }
        }

        return $audioProductions;
    }

    /**
     * 更新音频制作记录
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
            'audio_name', 'description', 'character_id', 'voice_actor', 'voice_style',
            'language', 'duration', 'script_text', 'emotion_tone', 'volume_level',
            'audio_settings', 'file_format', 'file_size', 'file_url', 'preview_url',
            'generation_cost', 'quality_score', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if ($field === 'audio_settings') {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_audio_productions` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除音频制作记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_audio_productions` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI生成音频
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildAudioPrompt($params);
        
        // 这里应该调用AI服务生成音频
        // 暂时返回模拟数据
        return [
            'audio_name' => $params['audio_name'] ?? '未命名音频',
            'audio_type' => $params['audio_type'] ?? 'voice_over',
            'description' => '基于AI生成的音频内容',
            'voice_actor' => $params['voice_actor'] ?? 'AI语音助手',
            'voice_style' => $params['voice_style'] ?? '自然',
            'language' => $params['language'] ?? 'zh-CN',
            'duration' => self::estimateDuration($params['script_text'] ?? ''),
            'script_text' => $params['script_text'] ?? '',
            'emotion_tone' => $params['emotion_tone'] ?? '中性',
            'volume_level' => $params['volume_level'] ?? 1.00,
            'audio_settings' => [
                'sample_rate' => 44100,
                'bit_rate' => 128,
                'channels' => 2,
                'format' => 'mp3',
                'quality' => 'high'
            ],
            'file_format' => 'mp3',
            'generation_cost' => self::calculateCost($params)
        ];
    }

    /**
     * 构建音频生成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildAudioPrompt(array $params): string
    {
        $prompt = "动漫音频制作提示词：\n";
        $prompt .= "你是一位音效师/配乐师。请根据以下动漫片段和要求生成音频方案：\n";
        
        if (!empty($params['animation_segment_description'])) {
            $prompt .= "【动漫片段描述】：{$params['animation_segment_description']}\n";
        }
        
        if (!empty($params['character_dialogue'])) {
            $prompt .= "【角色对话】：{$params['character_dialogue']}\n";
        }
        
        if (!empty($params['scene_atmosphere'])) {
            $prompt .= "【场景氛围】：{$params['scene_atmosphere']}\n";
        }
        
        if (!empty($params['emotional_tone'])) {
            $prompt .= "【情绪要求】：{$params['emotional_tone']}\n";
        }

        $prompt .= "\n请生成包含以下内容的音频制作方案：
1. 配音建议
   - 角色配音风格
   - 情绪表达
   - 语速、语调
   
2. 音效设计
   - 场景音效（风声、水声、脚步声）
   - 动作音效（打击、摩擦、爆炸）
   - 特殊音效
   
3. 背景音乐
   - 音乐风格
   - 情绪匹配
   - 节奏、旋律
   
4. 混音建议
   - 各音轨音量平衡
   - 空间感营造

要求：声音与画面高度匹配、烘托氛围、提升观感。";

        return $prompt;
    }

    /**
     * 估算音频时长
     *
     * @param string $scriptText
     * @return float
     */
    private static function estimateDuration(string $scriptText): float
    {
        if (empty($scriptText)) {
            return 0.0;
        }
        
        // 中文平均语速约为200字/分钟
        $charCount = mb_strlen($scriptText, 'UTF-8');
        $durationMinutes = $charCount / 200;
        return round($durationMinutes * 60, 2); // 转换为秒
    }

    /**
     * 计算生成成本
     *
     * @param array $params
     * @return float
     */
    private static function calculateCost(array $params): float
    {
        $baseCost = 0.01; // 基础成本
        $duration = self::estimateDuration($params['script_text'] ?? '');
        
        // 按时长计费
        $durationCost = $duration * 0.001;
        
        // 根据音频类型调整成本
        $typeMultiplier = 1.0;
        switch ($params['audio_type'] ?? 'voice_over') {
            case 'voice_over':
                $typeMultiplier = 1.0;
                break;
            case 'background_music':
                $typeMultiplier = 1.5;
                break;
            case 'sound_effect':
                $typeMultiplier = 0.8;
                break;
        }
        
        return round(($baseCost + $durationCost) * $typeMultiplier, 4);
    }

    /**
     * 获取音频统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getAudioStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_audio,
                    COUNT(CASE WHEN audio_type = 'voice_over' THEN 1 END) as voice_over_count,
                    COUNT(CASE WHEN audio_type = 'sound_effect' THEN 1 END) as sound_effect_count,
                    COUNT(CASE WHEN audio_type = 'background_music' THEN 1 END) as background_music_count,
                    COUNT(CASE WHEN audio_type = 'dialogue' THEN 1 END) as dialogue_count,
                    COUNT(CASE WHEN audio_type = 'narration' THEN 1 END) as narration_count,
                    SUM(duration) as total_duration,
                    SUM(file_size) as total_file_size,
                    SUM(generation_cost) as total_generation_cost,
                    AVG(quality_score) as avg_quality_score,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_audio,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_audio,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_audio
                FROM `{$prefix}anime_audio_productions` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新音频状态
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

        $sql = "UPDATE `{$prefix}anime_audio_productions` SET status = :status";
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
     * 获取角色的配音列表
     *
     * @param int $characterId
     * @return array
     */
    public static function getByCharacter(int $characterId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ap.*, p.title as project_title 
                FROM `{$prefix}anime_audio_productions` ap 
                LEFT JOIN `{$prefix}anime_projects` p ON ap.project_id = p.id 
                WHERE ap.character_id = :character_id AND ap.audio_type = 'voice_over' 
                ORDER BY ap.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':character_id' => $characterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取剧集的音频列表
     *
     * @param int $episodeScriptId
     * @return array
     */
    public static function getByEpisode(int $episodeScriptId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ap.*, c.name as character_name 
                FROM `{$prefix}anime_audio_productions` ap 
                LEFT JOIN `{$prefix}anime_characters` c ON ap.character_id = c.id 
                WHERE ap.episode_script_id = :episode_script_id 
                ORDER BY ap.audio_type, ap.created_at";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':episode_script_id' => $episodeScriptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 批量创建音频记录
     *
     * @param array $audioList
     * @return array
     */
    public static function createBatch(array $audioList): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $createdIds = [];
        $errors = [];
        
        $pdo->beginTransaction();
        
        try {
            foreach ($audioList as $audio) {
                $id = self::create($audio);
                if ($id) {
                    $createdIds[] = $id;
                } else {
                    $errors[] = "创建音频失败: " . json_encode($audio);
                }
            }
            
            if (empty($errors)) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errors[] = "数据库错误: " . $e->getMessage();
        }
        
        return [
            'success' => empty($errors),
            'created_ids' => $createdIds,
            'errors' => $errors
        ];
    }

    /**
     * 获取高质量音频列表
     *
     * @param int $projectId
     * @param float $minQualityScore
     * @return array
     */
    public static function getHighQualityAudio(int $projectId, float $minQualityScore = 8.0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_audio_productions` 
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