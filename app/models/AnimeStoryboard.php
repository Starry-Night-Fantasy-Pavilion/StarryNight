<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫分镜模型
 */
class AnimeStoryboard
{
    /**
     * 创建分镜
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_storyboards` 
                (project_id, episode_script_id, scene_number, shot_number, shot_type, camera_angle, camera_movement, duration, description, action_description, dialogue, sound_notes, music_notes, composition_notes, lighting_notes, character_positions, background_notes, special_effects, transition_type, storyboard_image_url, reference_image_url, status, sort_order) 
                VALUES (:project_id, :episode_script_id, :scene_number, :shot_number, :shot_type, :camera_angle, :camera_movement, :duration, :description, :action_description, :dialogue, :sound_notes, :music_notes, :composition_notes, :lighting_notes, :character_positions, :background_notes, :special_effects, :transition_type, :storyboard_image_url, :reference_image_url, :status, :sort_order)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':episode_script_id' => $data['episode_script_id'] ?? null,
            ':scene_number' => $data['scene_number'],
            ':shot_number' => $data['shot_number'],
            ':shot_type' => $data['shot_type'] ?? 'medium',
            ':camera_angle' => $data['camera_angle'] ?? 'eye_level',
            ':camera_movement' => $data['camera_movement'] ?? 'static',
            ':duration' => $data['duration'] ?? 0.00,
            ':description' => $data['description'] ?? null,
            ':action_description' => $data['action_description'] ?? null,
            ':dialogue' => $data['dialogue'] ?? null,
            ':sound_notes' => $data['sound_notes'] ?? null,
            ':music_notes' => $data['music_notes'] ?? null,
            ':composition_notes' => $data['composition_notes'] ?? null,
            ':lighting_notes' => $data['lighting_notes'] ?? null,
            ':character_positions' => $data['character_positions'] ?? null,
            ':background_notes' => $data['background_notes'] ?? null,
            ':special_effects' => $data['special_effects'] ?? null,
            ':transition_type' => $data['transition_type'] ?? 'cut',
            ':storyboard_image_url' => $data['storyboard_image_url'] ?? null,
            ':reference_image_url' => $data['reference_image_url'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':sort_order' => $data['sort_order'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取分镜
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT sb.*, ap.title as project_title, aes.title as episode_title 
                FROM `{$prefix}anime_storyboards` sb 
                LEFT JOIN `{$prefix}anime_projects` ap ON sb.project_id = ap.id 
                LEFT JOIN `{$prefix}anime_episode_scripts` aes ON sb.episode_script_id = aes.id 
                WHERE sb.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的分镜列表
     *
     * @param int $projectId
     * @param array $filters
     * @return array
     */
    public static function getByProject(int $projectId, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["sb.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['episode_script_id'])) {
            $where[] = "sb.episode_script_id = :episode_script_id";
            $params[':episode_script_id'] = $filters['episode_script_id'];
        }

        if (!empty($filters['scene_number'])) {
            $where[] = "sb.scene_number = :scene_number";
            $params[':scene_number'] = $filters['scene_number'];
        }

        if (!empty($filters['status'])) {
            $where[] = "sb.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql = "SELECT sb.*, aes.title as episode_title 
                FROM `{$prefix}anime_storyboards` sb 
                LEFT JOIN `{$prefix}anime_episode_scripts` aes ON sb.episode_script_id = aes.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY sb.scene_number, sb.shot_number, sb.sort_order";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取剧集的分镜列表
     *
     * @param int $episodeScriptId
     * @return array
     */
    public static function getByEpisode(int $episodeScriptId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT sb.*, ap.title as project_title 
                FROM `{$prefix}anime_storyboards` sb 
                LEFT JOIN `{$prefix}anime_projects` ap ON sb.project_id = ap.id 
                WHERE sb.episode_script_id = :episode_script_id 
                ORDER BY sb.scene_number, sb.shot_number, sb.sort_order";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':episode_script_id' => $episodeScriptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新分镜
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
            'scene_number', 'shot_number', 'shot_type', 'camera_angle', 'camera_movement',
            'duration', 'description', 'action_description', 'dialogue', 'sound_notes',
            'music_notes', 'composition_notes', 'lighting_notes', 'character_positions',
            'background_notes', 'special_effects', 'transition_type', 'storyboard_image_url',
            'reference_image_url', 'status', 'sort_order'
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

        $sql = "UPDATE `{$prefix}anime_storyboards` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除分镜
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_storyboards` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI生成分镜
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildStoryboardPrompt($params);
        
        // 尝试调用AI服务生成分镜
        $aiResult = self::callAIService($prompt, $params);
        
        // 只有当 AI 解析出有效分镜列表时才直接返回；否则降级到规则引擎
        if ($aiResult && isset($aiResult['success']) && $aiResult['success'] && !empty($aiResult['storyboards'])) {
            return $aiResult['storyboards'];
        }
        
        // 如果AI服务不可用，使用规则引擎生成模拟数据
        $storyboards = [];
        $shotCount = $params['estimated_shots'] ?? 5;
        
        for ($i = 1; $i <= $shotCount; $i++) {
            $storyboards[] = [
                'scene_number' => $params['scene_number'] ?? 1,
                'shot_number' => $i,
                'shot_type' => self::getRandomShotType(),
                'camera_angle' => self::getRandomCameraAngle(),
                'camera_movement' => self::getRandomCameraMovement(),
                'duration' => rand(3, 8) + (rand(0, 99) / 100),
                'description' => "镜头{$i}的描述，展现关键情节",
                'action_description' => "角色动作和场景变化的描述",
                'dialogue' => $i <= 2 ? "重要对话内容" : null,
                'sound_notes' => "音效说明",
                'music_notes' => "背景音乐建议",
                'composition_notes' => "构图和视觉元素说明",
                'lighting_notes' => "光线和氛围营造",
                'character_positions' => "角色在画面中的位置",
                'background_notes' => "背景元素描述",
                'special_effects' => $i === $shotCount ? "转场特效" : null,
                'transition_type' => $i === $shotCount ? 'fade' : 'cut'
            ];
        }
        
        return $storyboards;
    }
    
    /**
     * 调用AI服务生成分镜
     */
    private static function callAIService(string $prompt, array $params): ?array
    {
        try {
            $channels = \app\models\AIChannel::all();
            $channels = array_filter($channels, function($ch) {
                return ($ch['status'] ?? '') === 'enabled';
            });

            if (empty($channels)) {
                return null;
            }

            $channel = $channels[0];
            $baseUrl = $channel['base_url'] ?? '';
            $apiKey = $channel['api_key'] ?? '';

            if (empty($baseUrl) || empty($apiKey)) {
                return null;
            }

            $model = 'gpt-3.5-turbo';
            $modelsText = $channel['models_text'] ?? '';
            if ($modelsText) {
                $models = array_filter(array_map('trim', explode("\n", $modelsText)));
                $model = $models[0] ?? 'gpt-3.5-turbo';
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseUrl, '/') . '/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 3000,
                'temperature' => 0.8,
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['choices'][0]['message']['content'])) {
                return null;
            }

            // 解析AI返回的分镜数据
            $storyboards = self::parseAIStoryboardResponse($data['choices'][0]['message']['content'], $params);
            
            return [
                'success' => true,
                'storyboards' => $storyboards,
            ];

        } catch (\Exception $e) {
            error_log('AI storyboard generation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 解析AI返回的分镜响应
     */
    private static function parseAIStoryboardResponse(string $content, array $params): array
    {
        $content = trim($content);

        // 1) 直接尝试解析整个内容（AI有时会直接返回 JSON）
        $direct = json_decode($content, true);
        if (is_array($direct)) {
            if (isset($direct['storyboards']) && is_array($direct['storyboards'])) {
                return $direct['storyboards'];
            }
            // 直接返回分镜数组
            if (isset($direct[0]) && is_array($direct[0])) {
                return $direct;
            }
        }

        // 2) 尝试从 Markdown 代码块中提取 JSON
        $codeBlockMatch = [];
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $content, $codeBlockMatch)) {
            $maybeJson = trim($codeBlockMatch[1]);
            $decoded = json_decode($maybeJson, true);
            if (is_array($decoded)) {
                if (isset($decoded['storyboards']) && is_array($decoded['storyboards'])) {
                    return $decoded['storyboards'];
                }
                if (isset($decoded[0]) && is_array($decoded[0])) {
                    return $decoded;
                }
            }
        }

        // 3) 兜底：从文本中抓取一个 JSON 对象或数组片段
        $jsonMatch = [];
        if (preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/', $content, $jsonMatch)) {
            $decoded = json_decode($jsonMatch[1], true);
            if (is_array($decoded)) {
                if (isset($decoded['storyboards']) && is_array($decoded['storyboards'])) {
                    return $decoded['storyboards'];
                }
                if (isset($decoded[0]) && is_array($decoded[0])) {
                    return $decoded;
                }
            }
        }

        // 如果解析失败，返回空数组（上层会降级到规则引擎）
        return [];
    }

    /**
     * 构建分镜生成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildStoryboardPrompt(array $params): string
    {
        $prompt = "动漫分镜生成提示词：\n";
        $prompt .= "你是一位分镜师。请根据以下脚本内容生成分镜草图描述：\n";
        
        if (!empty($params['script_segment'])) {
            $prompt .= "【脚本内容】：{$params['script_segment']}\n";
        }
        
        if (!empty($params['character_info'])) {
            $prompt .= "【角色信息】：{$params['character_info']}\n";
        }
        
        if (!empty($params['scene_info'])) {
            $prompt .= "【场景信息】：{$params['scene_info']}\n";
        }

        $prompt .= "\n请生成包含以下内容的分镜描述：
1. 镜头编号
2. 镜头画面描述
   - 构图（远景、中景、特写）
   - 角色动作、表情
   - 场景细节
   
3. 运镜方式
   - 推、拉、摇、移、跟
   - 景别变化
   
4. 对白/旁白
   - 对应画面的台词
   
5. 画面时间预估

要求：画面感强、叙事流畅、符合脚本意图。";

        return $prompt;
    }

    /**
     * 获取随机镜头类型
     */
    private static function getRandomShotType(): string
    {
        $types = ['extreme_long', 'long', 'medium_long', 'medium', 'medium_close', 'close', 'extreme_close'];
        return $types[array_rand($types)];
    }

    /**
     * 获取随机镜头角度
     */
    private static function getRandomCameraAngle(): string
    {
        $angles = ['eye_level', 'high_angle', 'low_angle', 'dutch_angle', 'bird_eye', 'worm_eye'];
        return $angles[array_rand($angles)];
    }

    /**
     * 获取随机镜头运动
     */
    private static function getRandomCameraMovement(): string
    {
        $movements = ['static', 'pan', 'tilt', 'dolly', 'zoom', 'crane', 'handheld', 'tracking'];
        return $movements[array_rand($movements)];
    }

    /**
     * 获取分镜统计信息
     *
     * @param int $projectId
     * @param int|null $episodeScriptId
     * @return array
     */
    public static function getStoryboardStats(int $projectId, ?int $episodeScriptId = null): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if ($episodeScriptId) {
            $where[] = "episode_script_id = :episode_script_id";
            $params[':episode_script_id'] = $episodeScriptId;
        }

        $sql = "SELECT 
                    COUNT(*) as total_shots,
                    COUNT(CASE WHEN shot_type = 'extreme_long' THEN 1 END) as extreme_long_shots,
                    COUNT(CASE WHEN shot_type = 'long' THEN 1 END) as long_shots,
                    COUNT(CASE WHEN shot_type = 'medium' THEN 1 END) as medium_shots,
                    COUNT(CASE WHEN shot_type = 'close' THEN 1 END) as close_shots,
                    COUNT(CASE WHEN shot_type = 'extreme_close' THEN 1 END) as extreme_close_shots,
                    SUM(duration) as total_duration,
                    COUNT(CASE WHEN camera_movement != 'static' THEN 1 END) as moving_shots,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_shots
                FROM `{$prefix}anime_storyboards` 
                WHERE " . implode(' AND ', $where);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 批量创建分镜
     *
     * @param array $storyboards
     * @return array
     */
    public static function createBatch(array $storyboards): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $createdIds = [];
        $errors = [];
        
        $pdo->beginTransaction();
        
        try {
            foreach ($storyboards as $storyboard) {
                $id = self::create($storyboard);
                if ($id) {
                    $createdIds[] = $id;
                } else {
                    $errors[] = "创建分镜失败: " . json_encode($storyboard);
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
     * 更新分镜状态
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus(int $id, string $status): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}anime_storyboards` SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
    }

    /**
     * 获取下一个镜头编号
     *
     * @param int $projectId
     * @param int $sceneNumber
     * @return int
     */
    public static function getNextShotNumber(int $projectId, int $sceneNumber): int
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT COALESCE(MAX(shot_number), 0) + 1 as next_shot 
                FROM `{$prefix}anime_storyboards` 
                WHERE project_id = :project_id AND scene_number = :scene_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':scene_number' => $sceneNumber
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['next_shot'] ?? 1;
    }
}