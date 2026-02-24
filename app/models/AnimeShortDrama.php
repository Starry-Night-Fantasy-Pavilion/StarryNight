<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * AI短剧制作模型
 */
class AnimeShortDrama
{
    /**
     * 创建短剧制作记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_short_dramas` 
                (project_id, title, description, theme, core_plot, character_settings, drama_style, duration_minutes, generation_prompt, ai_model, generation_parameters, video_url, preview_url, thumbnail_url, quality_score, generation_cost, generation_time, status, error_message) 
                VALUES (:project_id, :title, :description, :theme, :core_plot, :character_settings, :drama_style, :duration_minutes, :generation_prompt, :ai_model, :generation_parameters, :video_url, :preview_url, :thumbnail_url, :quality_score, :generation_cost, :generation_time, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':theme' => $data['theme'] ?? null,
            ':core_plot' => $data['core_plot'] ?? null,
            ':character_settings' => isset($data['character_settings']) ? json_encode($data['character_settings']) : null,
            ':drama_style' => $data['drama_style'] ?? 'daily',
            ':duration_minutes' => $data['duration_minutes'] ?? 5,
            ':generation_prompt' => $data['generation_prompt'] ?? null,
            ':ai_model' => $data['ai_model'] ?? null,
            ':generation_parameters' => isset($data['generation_parameters']) ? json_encode($data['generation_parameters']) : null,
            ':video_url' => $data['video_url'] ?? null,
            ':preview_url' => $data['preview_url'] ?? null,
            ':thumbnail_url' => $data['thumbnail_url'] ?? null,
            ':quality_score' => $data['quality_score'] ?? null,
            ':generation_cost' => $data['generation_cost'] ?? 0.0000,
            ':generation_time' => $data['generation_time'] ?? 0,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取短剧制作记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT sd.*, p.title as project_title 
                FROM `{$prefix}anime_short_dramas` sd 
                LEFT JOIN `{$prefix}anime_projects` p ON sd.project_id = p.id 
                WHERE sd.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // 解析JSON字段
            if ($result['character_settings']) {
                $result['character_settings'] = json_decode($result['character_settings'], true);
            }
            if ($result['generation_parameters']) {
                $result['generation_parameters'] = json_decode($result['generation_parameters'], true);
            }
        }
        
        return $result;
    }

    /**
     * 获取项目的短剧制作记录列表
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

        $where = ["sd.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['drama_style'])) {
            $where[] = "sd.drama_style = :drama_style";
            $params[':drama_style'] = $filters['drama_style'];
        }

        if (!empty($filters['status'])) {
            $where[] = "sd.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['theme'])) {
            $where[] = "sd.theme LIKE :theme";
            $params[':theme'] = '%' . $filters['theme'] . '%';
        }

        $sql = "SELECT sd.* 
                FROM `{$prefix}anime_short_dramas` sd 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY sd.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $shortDramas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($shortDramas as &$drama) {
            if ($drama['character_settings']) {
                $drama['character_settings'] = json_decode($drama['character_settings'], true);
            }
            if ($drama['generation_parameters']) {
                $drama['generation_parameters'] = json_decode($drama['generation_parameters'], true);
            }
        }

        return $shortDramas;
    }

    /**
     * 更新短剧制作记录
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
            'title', 'description', 'theme', 'core_plot', 'character_settings',
            'drama_style', 'duration_minutes', 'generation_prompt', 'ai_model',
            'generation_parameters', 'video_url', 'preview_url', 'thumbnail_url',
            'quality_score', 'generation_cost', 'generation_time', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "`{$field}` = :{$field}";
                if (in_array($field, ['character_settings', 'generation_parameters'])) {
                    $params[":{$field}"] = json_encode($data[$field]);
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_short_dramas` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 删除短剧制作记录
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_short_dramas` WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * 使用AI生成短剧
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildShortDramaPrompt($params);
        
        // 这里应该调用AI服务生成短剧
        // 暂时返回模拟数据
        return [
            'title' => $params['title'] ?? '未命名短剧',
            'description' => '基于AI生成的短剧内容',
            'theme' => $params['theme'] ?? '日常生活',
            'core_plot' => self::generateMockPlot($params),
            'character_settings' => self::generateMockCharacters($params),
            'drama_style' => $params['drama_style'] ?? 'daily',
            'duration_minutes' => $params['duration_minutes'] ?? 5,
            'generation_prompt' => $prompt,
            'ai_model' => $params['ai_model'] ?? 'sora2',
            'generation_parameters' => [
                'quality' => 'high',
                'style_strength' => 0.8,
                'motion_strength' => 0.7,
                'detail_level' => 'medium'
            ],
            'generation_cost' => self::calculateGenerationCost($params),
            'generation_time' => self::estimateGenerationTime($params)
        ];
    }

    /**
     * 构建短剧生成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildShortDramaPrompt(array $params): string
    {
        $prompt = "AI短剧生成提示词：\n";
        $prompt .= "你是一位短剧导演。请根据以下描述生成一部短剧：\n";
        
        if (!empty($params['short_drama_theme'])) {
            $prompt .= "【短剧主题】：{$params['short_drama_theme']}\n";
        }
        
        if (!empty($params['core_plot'])) {
            $prompt .= "【核心剧情】：{$params['core_plot']}\n";
        }
        
        if (!empty($params['character_settings'])) {
            $prompt .= "【角色设定】：{$params['character_settings']}\n";
        }
        
        if (!empty($params['drama_style'])) {
            $prompt .= "【风格要求】：{$params['drama_style']}\n";
        }
        
        if (!empty($params['duration_minutes'])) {
            $prompt .= "【时长要求】：{$params['duration_minutes']} 分钟\n";
        }

        $prompt .= "\n请生成包含以下内容的短剧方案：
1. 剧情梗概
2. 关键场景描述
3. 主要角色对话
4. 情绪转折点
5. 音乐与音效建议
6. 视觉风格建议

要求：情节紧凑、冲突明显、情感饱满、符合短剧特点。";

        return $prompt;
    }

    /**
     * 生成模拟剧情
     *
     * @param array $params
     * @return string
     */
    private static function generateMockPlot(array $params): string
    {
        $style = $params['drama_style'] ?? 'daily';
        
        $plots = [
            'comedy' => '一个普通的上班族在日常生活中遇到的一系列搞笑事件，通过幽默的方式展现现代人的生活压力和应对方式。',
            'tragedy' => '一个关于失去与重生的故事，主角在面对人生重大挫折后，如何重新找到生活的意义。',
            'scifi' => '在未来世界中，一个普通人意外获得了超能力，但这份能力带来的却是意想不到的麻烦。',
            'romance' => '两个性格迥异的人在偶然相遇后，从误解到理解，最终找到真爱的温暖故事。',
            'daily' => '记录邻里之间的温馨小事，展现平凡生活中的真情实感。',
            'fantasy' => '一个现代青年意外穿越到奇幻世界，在这个新世界中寻找回家之路的冒险故事。',
            'horror' => '一座古老宅邸中发生的神秘事件，主角们需要揭开隐藏在黑暗中的真相。',
            'thriller' => '一场看似简单的案件背后隐藏着复杂的阴谋，侦探需要在时间耗尽前找到真相。'
        ];
        
        return $plots[$style] ?? $plots['daily'];
    }

    /**
     * 生成模拟角色设定
     *
     * @param array $params
     * @return array
     */
    private static function generateMockCharacters(array $params): array
    {
        return [
            [
                'name' => '小明',
                'age' => 25,
                'occupation' => '上班族',
                'personality' => '乐观开朗，但有时会犯迷糊',
                'appearance' => '中等身材，穿着休闲，总是带着微笑',
                'role' => '主角'
            ],
            [
                'name' => '小红',
                'age' => 24,
                'occupation' => '设计师',
                'personality' => '细心体贴，善于观察',
                'appearance' => '长发，穿着时尚，眼神温柔',
                'role' => '女主角'
            ],
            [
                'name' => '老王',
                'age' => 45,
                'occupation' => '邻居',
                'personality' => '热心肠，喜欢帮助别人',
                'appearance' => '微胖，总是穿着舒适的家居服',
                'role' => '配角'
            ]
        ];
    }

    /**
     * 计算生成成本
     *
     * @param array $params
     * @return float
     */
    private static function calculateGenerationCost(array $params): float
    {
        $duration = $params['duration_minutes'] ?? 5;
        $quality = $params['quality'] ?? 'high';
        $model = $params['ai_model'] ?? 'sora2';
        
        $baseCost = $duration * 0.5; // 基础成本：每分钟0.5
        
        // 质量调整
        $qualityMultiplier = [
            'low' => 0.5,
            'medium' => 1.0,
            'high' => 2.0,
            'ultra' => 4.0
        ];
        
        $baseCost *= $qualityMultiplier[$quality] ?? 1.0;
        
        // 模型调整
        $modelMultiplier = [
            'sora2' => 1.0,
            'pxz_ai' => 0.8,
            'seko_ai' => 1.2
        ];
        
        $baseCost *= $modelMultiplier[$model] ?? 1.0;
        
        return round($baseCost, 4);
    }

    /**
     * 估算生成时间
     *
     * @param array $params
     * @return int
     */
    private static function estimateGenerationTime(array $params): int
    {
        $duration = $params['duration_minutes'] ?? 5;
        $quality = $params['quality'] ?? 'high';
        $model = $params['ai_model'] ?? 'sora2';
        
        $baseTime = $duration * 60; // 基础时间：每分钟60秒
        
        // 质量调整
        $qualityMultiplier = [
            'low' => 0.5,
            'medium' => 1.0,
            'high' => 2.0,
            'ultra' => 4.0
        ];
        
        $baseTime *= $qualityMultiplier[$quality] ?? 1.0;
        
        // 模型调整
        $modelMultiplier = [
            'sora2' => 1.0,
            'pxz_ai' => 0.7,
            'seko_ai' => 1.3
        ];
        
        $baseTime *= $modelMultiplier[$model] ?? 1.0;
        
        return intval($baseTime);
    }

    /**
     * 获取短剧统计信息
     *
     * @param int $projectId
     * @return array
     */
    public static function getShortDramaStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_dramas,
                    COUNT(CASE WHEN drama_style = 'comedy' THEN 1 END) as comedy_dramas,
                    COUNT(CASE WHEN drama_style = 'tragedy' THEN 1 END) as tragedy_dramas,
                    COUNT(CASE WHEN drama_style = 'scifi' THEN 1 END) as scifi_dramas,
                    COUNT(CASE WHEN drama_style = 'romance' THEN 1 END) as romance_dramas,
                    COUNT(CASE WHEN drama_style = 'daily' THEN 1 END) as daily_dramas,
                    COUNT(CASE WHEN drama_style = 'fantasy' THEN 1 END) as fantasy_dramas,
                    COUNT(CASE WHEN drama_style = 'horror' THEN 1 END) as horror_dramas,
                    COUNT(CASE WHEN drama_style = 'thriller' THEN 1 END) as thriller_dramas,
                    SUM(duration_minutes) as total_duration,
                    SUM(generation_cost) as total_generation_cost,
                    SUM(generation_time) as total_generation_time,
                    AVG(quality_score) as avg_quality_score,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_dramas,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_dramas,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_dramas
                FROM `{$prefix}anime_short_dramas` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新短剧状态
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

        $sql = "UPDATE `{$prefix}anime_short_dramas` SET status = :status";
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
     * 获取高质量短剧列表
     *
     * @param int $projectId
     * @param float $minQualityScore
     * @return array
     */
    public static function getHighQualityDramas(int $projectId, float $minQualityScore = 8.0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_short_dramas` 
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

    /**
     * 获取热门短剧列表
     *
     * @param int $limit
     * @return array
     */
    public static function getPopularDramas(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT sd.*, p.title as project_title, u.username as creator_username 
                FROM `{$prefix}anime_short_dramas` sd 
                LEFT JOIN `{$prefix}anime_projects` p ON sd.project_id = p.id 
                LEFT JOIN `{$prefix}users` u ON p.user_id = u.id 
                WHERE sd.status = 'completed' AND sd.quality_score >= 7.0 
                ORDER BY sd.quality_score DESC, sd.created_at DESC 
                LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 批量生成短剧
     *
     * @param array $dramaList
     * @return array
     */
    public static function generateBatch(array $dramaList): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();
        
        $createdIds = [];
        $errors = [];
        
        $pdo->beginTransaction();
        
        try {
            foreach ($dramaList as $drama) {
                $id = self::create($drama);
                if ($id) {
                    $createdIds[] = $id;
                } else {
                    $errors[] = "创建短剧失败: " . json_encode($drama);
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
}