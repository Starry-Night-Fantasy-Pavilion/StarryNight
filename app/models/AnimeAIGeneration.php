<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫AI生成记录模型
 */
class AnimeAIGeneration
{
    /**
     * 创建AI生成记录
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_ai_generations` 
                (project_id, generation_type, target_id, prompt, ai_model, parameters, result, result_url, tokens_used, cost, generation_time, quality_score, user_feedback, status, error_message) 
                VALUES (:project_id, :generation_type, :target_id, :prompt, :ai_model, :parameters, :result, :result_url, :tokens_used, :cost, :generation_time, :quality_score, :user_feedback, :status, :error_message)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':generation_type' => $data['generation_type'],
            ':target_id' => $data['target_id'] ?? null,
            ':prompt' => $data['prompt'],
            ':ai_model' => $data['ai_model'] ?? null,
            ':parameters' => $data['parameters'] ? json_encode($data['parameters']) : null,
            ':result' => $data['result'] ?? null,
            ':result_url' => $data['result_url'] ?? null,
            ':tokens_used' => $data['tokens_used'] ?? 0,
            ':cost' => $data['cost'] ?? 0.0000,
            ':generation_time' => $data['generation_time'] ?? 0,
            ':quality_score' => $data['quality_score'] ?? 0.00,
            ':user_feedback' => $data['user_feedback'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':error_message' => $data['error_message'] ?? null
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取AI生成记录
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT ag.*, ap.title as project_title, u.username as creator_username 
                FROM `{$prefix}anime_ai_generations` ag 
                LEFT JOIN `{$prefix}anime_projects` ap ON ag.project_id = ap.id 
                LEFT JOIN `{$prefix}users` u ON ap.user_id = u.id 
                WHERE ag.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['parameters']) {
            $result['parameters'] = json_decode($result['parameters'], true);
        }
        
        return $result;
    }

    /**
     * 获取项目的AI生成记录列表
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

        $where = ["ag.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['generation_type'])) {
            $where[] = "ag.generation_type = :generation_type";
            $params[':generation_type'] = $filters['generation_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "ag.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['target_id'])) {
            $where[] = "ag.target_id = :target_id";
            $params[':target_id'] = $filters['target_id'];
        }

        $sql = "SELECT ag.*, ap.title as project_title 
                FROM `{$prefix}anime_ai_generations` ag 
                LEFT JOIN `{$prefix}anime_projects` ap ON ag.project_id = ap.id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY ag.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$result) {
            if ($result['parameters']) {
                $result['parameters'] = json_decode($result['parameters'], true);
            }
        }

        return $results;
    }

    /**
     * 获取用户的AI生成记录
     *
     * @param int $userId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getByUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["ag.user_id = :user_id"];
        $params = [':user_id' => $userId];

        if (!empty($filters['generation_type'])) {
            $where[] = "ag.generation_type = :generation_type";
            $params[':generation_type'] = $filters['generation_type'];
        }

        if (!empty($filters['project_id'])) {
            $where[] = "ag.project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }

        $sql = "SELECT ag.*, ap.title as project_title
                FROM `{$prefix}anime_ai_generations` ag
                LEFT JOIN `{$prefix}anime_projects` ap ON ag.project_id = ap.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ag.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 解析JSON字段
        foreach ($results as &$result) {
            if ($result['parameters']) {
                $result['parameters'] = json_decode($result['parameters'], true);
            }
        }

        return $results;
    }

    /**
     * 生成脚本内容
     *
     * @param int $projectId
     * @param int $episodeNumber
     * @param array $params
     * @return array|false
     */
    public static function generateScript(int $projectId, int $episodeNumber, array $params = []): array|false
    {
        $project = AnimeProject::getById($projectId);
        if (!$project) {
            return false;
        }

        // 构建脚本生成提示词
        $prompt = self::buildScriptPrompt($project, $episodeNumber, $params);
        
        // 记录生成请求
        $generationId = self::create([
            'project_id' => $projectId,
            'generation_type' => 'script',
            'target_id' => null, // 将在脚本创建后更新
            'prompt' => $prompt,
            'ai_model' => $params['ai_model'] ?? 'gpt-4',
            'parameters' => $params,
            'status' => 'processing'
        ]);

        if (!$generationId) {
            return false;
        }

        // 模拟AI生成过程
        $startTime = time();
        $generatedScript = self::simulateScriptGeneration($project, $episodeNumber, $params);
        $generationTime = time() - $startTime;
        $tokensUsed = strlen($prompt) + strlen($generatedScript);
        $cost = $tokensUsed * 0.00002; // 模拟成本计算

        // 更新生成记录
        self::update($generationId, [
            'result' => $generatedScript,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'generation_time' => $generationTime,
            'quality_score' => 8.5, // 模拟质量评分
            'status' => 'completed'
        ]);

        // 创建或更新分集脚本记录
        $episodeData = [
            'project_id' => $projectId,
            'episode_number' => $episodeNumber,
            'title' => "第{$episodeNumber}集",
            'duration' => $project['episode_duration'],
            'main_content' => $generatedScript,
            'status' => 'completed',
            'ai_generated' => 1,
            'word_count' => mb_strlen($generatedScript)
        ];

        // 检查是否已存在该集数的脚本
        $existingScript = self::getEpisodeScript($projectId, $episodeNumber);
        if ($existingScript) {
            // 更新现有脚本
            $episodeData['human_edited'] = 0; // 标记为AI生成但未人工编辑
            AnimeEpisodeScript::update($existingScript['id'], $episodeData);
            $scriptId = $existingScript['id'];
        } else {
            // 创建新脚本
            $scriptId = AnimeEpisodeScript::create($episodeData);
        }

        if ($scriptId) {
            // 更新生成记录的目标ID
            self::update($generationId, ['target_id' => $scriptId]);
        }

        return [
            'generation_id' => $generationId,
            'script_id' => $scriptId,
            'script_content' => $generatedScript,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'generation_time' => $generationTime
        ];
    }

    /**
     * 生成角色设计
     *
     * @param int $projectId
     * @param array $params
     * @return array|false
     */
    public static function generateCharacterDesign(int $projectId, array $params = []): array|false
    {
        $project = AnimeProject::getById($projectId);
        if (!$project) {
            return false;
        }

        // 构建角色设计提示词
        $prompt = self::buildCharacterPrompt($project, $params);
        
        // 记录生成请求
        $generationId = self::create([
            'project_id' => $projectId,
            'generation_type' => 'character_design',
            'target_id' => null,
            'prompt' => $prompt,
            'ai_model' => $params['ai_model'] ?? 'dall-e-3',
            'parameters' => $params,
            'status' => 'processing'
        ]);

        if (!$generationId) {
            return false;
        }

        // 模拟AI生成过程
        $startTime = time();
        $generatedDesign = self::simulateCharacterGeneration($project, $params);
        $generationTime = time() - $startTime;
        $tokensUsed = strlen($prompt);
        $cost = $tokensUsed * 0.00003; // 图像生成成本更高

        // 更新生成记录
        self::update($generationId, [
            'result' => $generatedDesign['description'],
            'result_url' => $generatedDesign['image_url'],
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'generation_time' => $generationTime,
            'quality_score' => 8.0,
            'status' => 'completed'
        ]);

        // 创建角色记录
        $characterData = [
            'project_id' => $projectId,
            'character_name' => $params['character_name'] ?? '新角色',
            'character_type' => $params['character_type'] ?? 'supporting',
            'appearance' => $generatedDesign['appearance'],
            'personality' => $generatedDesign['personality'],
            'background' => $generatedDesign['background'],
            'image_url' => $generatedDesign['image_url']
        ];

        $characterId = AnimeCharacter::create($characterData);
        if ($characterId) {
            self::update($generationId, ['target_id' => $characterId]);
        }

        return [
            'generation_id' => $generationId,
            'character_id' => $characterId,
            'character_data' => $generatedDesign,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'generation_time' => $generationTime
        ];
    }

    /**
     * 构建脚本生成提示词
     *
     * @param array $project
     * @param int $episodeNumber
     * @param array $params
     * @return string
     */
    private static function buildScriptPrompt(array $project, int $episodeNumber, array $params): string
    {
        $prompt = "动漫脚本生成提示词：\n";
        $prompt .= "请为以下动漫企划生成第{$episodeNumber}集的脚本：\n\n";
        
        $prompt .= "【企划信息】\n";
        $prompt .= "标题：{$project['title']}\n";
        $prompt .= "题材：{$project['genre']}\n";
        $prompt .= "核心概念：{$project['core_concept']}\n";
        $prompt .= "目标受众：{$project['target_audience']}\n";
        $prompt .= "单集时长：{$project['episode_duration']}分钟\n";
        
        if ($project['production_mode'] === 'short') {
            $prompt .= "制作模式：短剧模式\n";
        } else {
            $prompt .= "制作模式：长篇模式（包含片头片尾）\n";
            $prompt .= "片头时长：{$project['intro_duration']}分钟\n";
            $prompt .= "正片时长：{$project['main_content_duration']}分钟\n";
            $prompt .= "片尾时长：{$project['outro_duration']}分钟\n";
        }

        if (!empty($params['episode_focus'])) {
            $prompt .= "\n【本集重点】\n{$params['episode_focus']}\n";
        }

        if (!empty($params['characters_involved'])) {
            $prompt .= "\n【出场角色】\n{$params['characters_involved']}\n";
        }

        if (!empty($params['plot_points'])) {
            $prompt .= "\n【情节要点】\n{$params['plot_points']}\n";
        }

        $prompt .= "\n请生成包含以下内容的脚本：
1. 集数标题
2. 场景分解
3. 对话内容
4. 动作描述
5. 音效提示

要求：情节紧凑、对话自然、符合角色设定、时长控制在指定范围内。";

        return $prompt;
    }

    /**
     * 构建角色设计提示词
     *
     * @param array $project
     * @param array $params
     * @return string
     */
    private static function buildCharacterPrompt(array $project, array $params): string
    {
        $prompt = "动漫角色设计提示词：\n";
        $prompt .= "请为以下动漫企划设计角色：\n\n";
        
        $prompt .= "【企划信息】\n";
        $prompt .= "标题：{$project['title']}\n";
        $prompt .= "题材：{$project['genre']}\n";
        $prompt .= "核心概念：{$project['core_concept']}\n";
        $prompt .= "目标受众：{$project['target_audience']}\n";

        if (!empty($params['character_name'])) {
            $prompt .= "\n【角色信息】\n";
            $prompt .= "姓名：{$params['character_name']}\n";
        }

        if (!empty($params['character_type'])) {
            $prompt .= "类型：{$params['character_type']}\n";
        }

        if (!empty($params['age'])) {
            $prompt .= "年龄：{$params['age']}\n";
        }

        if (!empty($params['gender'])) {
            $prompt .= "性别：{$params['gender']}\n";
        }

        if (!empty($params['personality_traits'])) {
            $prompt .= "性格特征：{$params['personality_traits']}\n";
        }

        if (!empty($params['background_story'])) {
            $prompt .= "背景故事：{$params['background_story']}\n";
        }

        if (!empty($params['visual_style'])) {
            $prompt .= "视觉风格：{$params['visual_style']}\n";
        }

        $prompt .= "\n请生成包含以下内容的角色设计：
1. 详细外貌描述
2. 服装设计
3. 性格特点
4. 背景故事
5. 角色形象图

要求：符合企划风格、形象鲜明、个性突出、具有辨识度。";

        return $prompt;
    }

    /**
     * 模拟脚本生成
     *
     * @param array $project
     * @param int $episodeNumber
     * @param array $params
     * @return string
     */
    private static function simulateScriptGeneration(array $project, int $episodeNumber, array $params): string
    {
        // 这里应该调用真实的AI服务
        // 暂时返回模拟内容
        $episodeTitle = isset($params['episode_title']) ? $params['episode_title'] : '新的冒险';
        $script = "第{$episodeNumber}集：{$episodeTitle}\n\n";
        $script .= "【场景一】\n";
        $script .= "时间：清晨\n";
        $script .= "地点：主角房间\n";
        $script .= "人物：主角、配角A\n\n";
        $script .= "（主角从床上醒来，阳光透过窗户洒进房间）\n\n";
        $script .= "主角：又是新的一天，今天会发生什么呢？\n\n";
        $script .= "配角A：快点起床，我们有个重要的任务！\n\n";
        $script .= "【场景二】\n";
        $script .= "时间：上午\n";
        $script .= "地点：学校/办公室\n";
        $script .= "人物：主角、配角B、反派\n\n";
        $script .= "（主角和配角B正在讨论，反派突然出现）\n\n";
        $script .= "反派：你们以为能阻止我的计划吗？\n\n";
        $script .= "主角：我们绝对不会让你得逞！\n\n";
        $script .= "【场景三】\n";
        $script .= "时间：下午\n";
        $script .= "地点：战斗场景\n";
        $script .= "人物：所有角色\n\n";
        $script .= "（激烈的战斗场面）\n\n";
        $script .= "主角：这是我们最后的机会！\n\n";
        $script .= "（主角使用必杀技，战斗结束）\n\n";
        $script .= "【场景四】\n";
        $script .= "时间：傍晚\n";
        $script .= "地点：休息场所\n";
        $script .= "人物：主角、配角们\n\n";
        $script .= "（角色们庆祝胜利，为下一集埋下伏笔）\n\n";
        $script .= "主角：虽然这次成功了，但更大的挑战还在后面...\n\n";
        
        return $script;
    }

    /**
     * 模拟角色生成
     *
     * @param array $project
     * @param array $params
     * @return array
     */
    private static function simulateCharacterGeneration(array $project, array $params): array
    {
        // 这里应该调用真实的AI服务
        // 暂时返回模拟内容
        return [
            'appearance' => '身高170cm，黑色长发，蓝色眼睛，穿着白色连衣裙',
            'personality' => '勇敢、善良、有正义感，但有时过于冲动',
            'background' => '来自普通家庭，因意外获得特殊能力而成为主角',
            'image_url' => '/generated/character_' . time() . '.png',
            'description' => '一个充满活力的年轻角色，具有强烈的正义感和保护他人的决心。'
        ];
    }

    /**
     * 获取分集脚本
     *
     * @param int $projectId
     * @param int $episodeNumber
     * @return array|false
     */
    private static function getEpisodeScript(int $projectId, int $episodeNumber)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT * FROM `{$prefix}anime_episode_scripts` 
                WHERE project_id = :project_id AND episode_number = :episode_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':episode_number' => $episodeNumber
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 更新AI生成记录
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
            'target_id', 'prompt', 'ai_model', 'parameters', 'result', 
            'result_url', 'tokens_used', 'cost', 'generation_time', 
            'quality_score', 'user_feedback', 'status', 'error_message'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'parameters') {
                    $fields[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
                } else {
                    $fields[] = "`{$field}` = :{$field}";
                    $params[":{$field}"] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE `{$prefix}anime_ai_generations` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 获取AI生成统计
     *
     * @param int $projectId
     * @return array
     */
    public static function getGenerationStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_generations,
                    COUNT(CASE WHEN generation_type = 'script' THEN 1 END) as script_generations,
                    COUNT(CASE WHEN generation_type = 'character_design' THEN 1 END) as character_generations,
                    COUNT(CASE WHEN generation_type = 'storyboard' THEN 1 END) as storyboard_generations,
                    COUNT(CASE WHEN generation_type = 'background' THEN 1 END) as background_generations,
                    COUNT(CASE WHEN generation_type = 'music' THEN 1 END) as music_generations,
                    COUNT(CASE WHEN generation_type = 'voice' THEN 1 END) as voice_generations,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_generations,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_generations,
                    COALESCE(SUM(tokens_used), 0) as total_tokens,
                    COALESCE(SUM(cost), 0) as total_cost,
                    COALESCE(AVG(quality_score), 0) as avg_quality_score,
                    COALESCE(AVG(generation_time), 0) as avg_generation_time
                FROM `{$prefix}anime_ai_generations` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取全局AI生成统计
     *
     * @return array
     */
    public static function getGlobalStats(): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_generations,
                    COUNT(DISTINCT project_id) as projects_using_ai,
                    COUNT(CASE WHEN generation_type = 'script' THEN 1 END) as script_generations,
                    COUNT(CASE WHEN generation_type = 'character_design' THEN 1 END) as character_generations,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_generations,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_generations,
                    COALESCE(SUM(tokens_used), 0) as total_tokens,
                    COALESCE(SUM(cost), 0) as total_cost,
                    COALESCE(AVG(quality_score), 0) as avg_quality_score
                FROM `{$prefix}anime_ai_generations`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}