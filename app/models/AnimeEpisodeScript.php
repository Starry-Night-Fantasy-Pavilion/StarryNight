<?php

namespace app\models;

use app\services\Database;
use PDO;

/**
 * 动漫分集脚本模型
 */
class AnimeEpisodeScript
{
    /**
     * 创建分集脚本
     *
     * @param array $data
     * @return int|false
     */
    public static function create(array $data)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "INSERT INTO `{$prefix}anime_episode_scripts` 
                (project_id, episode_number, title, duration, intro_content, main_content, outro_content, scene_breakdown, dialogue_summary, key_events, character_development, status, ai_generated, human_edited, word_count) 
                VALUES (:project_id, :episode_number, :title, :duration, :intro_content, :main_content, :outro_content, :scene_breakdown, :dialogue_summary, :key_events, :character_development, :status, :ai_generated, :human_edited, :word_count)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':project_id' => $data['project_id'],
            ':episode_number' => $data['episode_number'],
            ':title' => $data['title'] ?? null,
            ':duration' => $data['duration'] ?? 20,
            ':intro_content' => $data['intro_content'] ?? null,
            ':main_content' => $data['main_content'] ?? null,
            ':outro_content' => $data['outro_content'] ?? null,
            ':scene_breakdown' => $data['scene_breakdown'] ?? null,
            ':dialogue_summary' => $data['dialogue_summary'] ?? null,
            ':key_events' => $data['key_events'] ?? null,
            ':character_development' => $data['character_development'] ?? null,
            ':status' => $data['status'] ?? 'draft',
            ':ai_generated' => $data['ai_generated'] ?? 0,
            ':human_edited' => $data['human_edited'] ?? 0,
            ':word_count' => $data['word_count'] ?? 0
        ]) ? $pdo->lastInsertId() : false;
    }

    /**
     * 根据ID获取分集脚本
     *
     * @param int $id
     * @return array|false
     */
    public static function getById(int $id)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT aes.*, ap.title as project_title 
                FROM `{$prefix}anime_episode_scripts` aes 
                LEFT JOIN `{$prefix}anime_projects` ap ON aes.project_id = ap.id 
                WHERE aes.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据项目ID和集数获取脚本
     *
     * @param int $projectId
     * @param int $episodeNumber
     * @return array|false
     */
    public static function getByProjectAndEpisode(int $projectId, int $episodeNumber)
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT aes.*, ap.title as project_title 
                FROM `{$prefix}anime_episode_scripts` aes 
                LEFT JOIN `{$prefix}anime_projects` ap ON aes.project_id = ap.id 
                WHERE aes.project_id = :project_id AND aes.episode_number = :episode_number";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':project_id' => $projectId,
            ':episode_number' => $episodeNumber
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取项目的所有分集脚本
     *
     * @param int $projectId
     * @param array $filters
     * @return array
     */
    public static function getByProject(int $projectId, array $filters = []): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $where = ["aes.project_id = :project_id"];
        $params = [':project_id' => $projectId];

        if (!empty($filters['status'])) {
            $where[] = "aes.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['ai_generated'])) {
            $where[] = "aes.ai_generated = :ai_generated";
            $params[':ai_generated'] = $filters['ai_generated'];
        }

        $sql = "SELECT aes.* 
                FROM `{$prefix}anime_episode_scripts` aes 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY aes.episode_number";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新分集脚本
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
            'title', 'duration', 'intro_content', 'main_content', 'outro_content',
            'scene_breakdown', 'dialogue_summary', 'key_events', 'character_development',
            'status', 'ai_generated', 'human_edited', 'word_count'
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

        $sql = "UPDATE `{$prefix}anime_episode_scripts` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * 删除分集脚本
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "DELETE FROM `{$prefix}anime_episode_scripts` WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * 标记为人工编辑
     *
     * @param int $id
     * @return bool
     */
    public static function markAsHumanEdited(int $id): bool
    {
        return self::update($id, ['human_edited' => 1]);
    }

    /**
     * 获取项目的脚本统计
     *
     * @param int $projectId
     * @return array
     */
    public static function getProjectStats(int $projectId): array
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "SELECT 
                    COUNT(*) as total_episodes,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as reviewed_count,
                    COUNT(CASE WHEN ai_generated = 1 THEN 1 END) as ai_generated_count,
                    COUNT(CASE WHEN human_edited = 1 THEN 1 END) as human_edited_count,
                    SUM(word_count) as total_words,
                    AVG(word_count) as avg_words,
                    SUM(duration) as total_duration
                FROM `{$prefix}anime_episode_scripts` 
                WHERE project_id = :project_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 批量创建分集脚本
     *
     * @param int $projectId
     * @param int $episodeCount
     * @param int $duration
     * @return array 创建的脚本ID列表
     */
    public static function batchCreate(int $projectId, int $episodeCount, int $duration = 20): array
    {
        $createdIds = [];
        
        for ($i = 1; $i <= $episodeCount; $i++) {
            $scriptData = [
                'project_id' => $projectId,
                'episode_number' => $i,
                'title' => "第{$i}集",
                'duration' => $duration,
                'status' => 'draft'
            ];
            
            $scriptId = self::create($scriptData);
            if ($scriptId) {
                $createdIds[] = $scriptId;
            }
        }
        
        return $createdIds;
    }

    /**
     * 重新排序集数
     *
     * @param int $projectId
     * @param array $episodeOrders 格式: [script_id => new_episode_number]
     * @return bool
     */
    public static function reorderEpisodes(int $projectId, array $episodeOrders): bool
    {
        $pdo = Database::pdo();
        $prefix = Database::prefix();

        $sql = "UPDATE `{$prefix}anime_episode_scripts` SET episode_number = :episode_number WHERE id = :id AND project_id = :project_id";
        $stmt = $pdo->prepare($sql);

        foreach ($episodeOrders as $scriptId => $episodeNumber) {
            $result = $stmt->execute([
                ':id' => $scriptId,
                ':episode_number' => $episodeNumber,
                ':project_id' => $projectId
            ]);
            
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 复制脚本
     *
     * @param int $id
     * @param int $newProjectId
     * @param int $newEpisodeNumber
     * @return int|false
     */
    public static function duplicate(int $id, int $newProjectId, int $newEpisodeNumber): int|false
    {
        $original = self::getById($id);
        if (!$original) {
            return false;
        }

        $newData = $original;
        unset($newData['id'], $newData['project_title']);
        $newData['project_id'] = $newProjectId;
        $newData['episode_number'] = $newEpisodeNumber;
        $newData['title'] = $original['title'] . ' (副本)';
        $newData['status'] = 'draft';
        $newData['human_edited'] = 0;
        
        return self::create($newData);
    }

    /**
     * 使用AI生成脚本
     *
     * @param array $params
     * @return array|false
     */
    public static function generateWithAI(array $params): array
    {
        $prompt = self::buildScriptPrompt($params);
        
        // 这里应该调用AI服务生成脚本
        // 暂时返回模拟数据
        return [
            'title' => $params['title'] ?? "第{$params['episode_number']}集",
            'summary' => self::generateMockSummary($params),
            'content' => self::generateMockContent($params),
            'duration' => $params['duration'] ?? 20,
            'word_count' => self::estimateWordCount($params),
            'status' => 'draft'
        ];
    }

    /**
     * 构建脚本生成提示词
     *
     * @param array $params
     * @return string
     */
    private static function buildScriptPrompt(array $params): string
    {
        $prompt = "动漫脚本提示词：\n";
        $prompt .= "你是一位动漫编剧。请创作动漫脚本：\n";
        
        if (!empty($params['episode_number'])) {
            $prompt .= "【集数】：第 {$params['episode_number']} 集\n";
        }
        
        if (!empty($params['background'])) {
            $prompt .= "【故事背景】：{$params['background']}\n";
        }
        
        if (!empty($params['character_status'])) {
            $prompt .= "【角色状态】：{$params['character_status']}\n";
        }
        
        if (!empty($params['episode_theme'])) {
            $prompt .= "【本集主题】：{$params['episode_theme']}\n";
        }
        
        if (!empty($params['previous_summary'])) {
            $prompt .= "【前情提要】：{$params['previous_summary']}\n";
        }

        $prompt .= "\n请创作包含以下内容的脚本：
1. 场景列表
   - 场景描述
   - 人物对话
   - 动作指示
   - 镜头建议
   
2. 剧情发展
   - 主要事件
   - 冲突高潮
   - 情感变化
   
3. 角色互动
   - 对话内容
   - 肢体语言
   - 心理活动

要求：情节紧凑、对话生动、符合人物设定。";

        return $prompt;
    }

    /**
     * 生成模拟剧情简介
     *
     * @param array $params
     * @return string
     */
    private static function generateMockSummary(array $params): string
    {
        $episodeNumber = $params['episode_number'] ?? 1;
        $theme = $params['episode_theme'] ?? '日常冒险';
        
        $summaries = [
            1 => '主角在新的一天中遇到了意想不到的挑战，需要运用智慧和勇气来解决问题。',
            2 => '随着剧情发展，主角开始了解到更多关于这个世界的信息，同时新的角色登场。',
            3 => '冲突升级，主角必须在友情和责任之间做出艰难的选择。',
            4 => '过去的秘密被揭开，主角的信念受到考验，团队面临分裂的危机。',
            5 => '在关键时刻，主角找到了新的力量，但使用这股力量需要付出代价。'
        ];
        
        return $summaries[$episodeNumber] ?? '主角面临新的挑战，通过努力和朋友的帮助，最终克服困难获得成长。';
    }

    /**
     * 生成模拟脚本内容
     *
     * @param array $params
     * @return string
     */
    private static function generateMockContent(array $params): string
    {
        $episodeNumber = $params['episode_number'] ?? 1;
        $duration = $params['duration'] ?? 20;
        
        $content = "【第{$episodeNumber}集】\n\n";
        
        // 开场场景
        $content .= "【场景1：清晨的街道】\n";
        $content .= "时间：早上7点\n";
        $content .= "地点：繁华的商业街\n";
        $content .= "人物：主角、路人\n\n";
        $content .= "【画面】\n";
        $content .= "阳光透过高楼缝隙洒在街道上，行人匆匆而过。主角背着书包，快步走在人群中。\n\n";
        $content .= "【对话】\n";
        $content .= "主角：（自言自语）今天一定要准时到达，不能再迟到了！\n\n";
        
        // 发展场景
        $content .= "【场景2：学校教室】\n";
        $content .= "时间：早上8点\n";
        $content .= "地点：明亮的教室\n";
        $content .= "人物：主角、老师、同学\n\n";
        $content .= "【画面】\n";
        $content .= "主角气喘吁吁地跑进教室，刚好赶上上课铃响。老师站在讲台上，微笑着看着主角。\n\n";
        $content .= "【对话】\n";
        $content .= "老师：主角同学，今天很准时呢。\n";
        $content .= "主角：（鞠躬）老师好！我会继续努力的。\n\n";
        
        // 高潮场景
        $content .= "【场景3：操场】\n";
        $content .= "时间：下午3点\n";
        $content .= "地点：学校操场\n";
        $content .= "人物：主角、朋友、对手\n\n";
        $content .= "【画面】\n";
        $content .= "操场上正在进行体育比赛，主角在关键时刻展现出惊人的能力，让所有人震惊。\n\n";
        $content .= "【对话】\n";
        $content .= "朋友：主角，你太厉害了！\n";
        $content .= "对手：（不甘心）下次我一定会赢过你！\n";
        $content .= "主角：（微笑）大家一起努力吧！\n\n";
        
        // 结尾场景
        $content .= "【场景4：夕阳下的回家路】\n";
        $content .= "时间：傍晚6点\n";
        $content .= "地点：安静的住宅区街道\n";
        $content .= "人物：主角、朋友\n\n";
        $content .= "【画面】\n";
        $content .= "夕阳西下，主角和朋友并肩走在回家的路上，影子被拉得很长。\n\n";
        $content .= "【对话】\n";
        $content .= "朋友：今天真是充实的一天。\n";
        $content .= "主角：是啊，明天也会是新的开始。\n\n";
        $content .= "【画面】\n";
        $content .= "两人挥手告别，各自走向家的方向。画面渐渐变暗。\n\n";
        $content .= "【本集完】";
        
        return $content;
    }

    /**
     * 估算字数
     *
     * @param array $params
     * @return int
     */
    private static function estimateWordCount(array $params): int
    {
        $duration = $params['duration'] ?? 20;
        // 假设每分钟脚本约800字
        return intval($duration * 800);
    }

    /**
     * 使用AI续写脚本
     *
     * @param int $scriptId
     * @param array $params
     * @return array|false
     */
    public static function continueWithAI(int $scriptId, array $params): array
    {
        $existingScript = self::getById($scriptId);
        if (!$existingScript) {
            return [];
        }

        $prompt = self::buildContinuePrompt($existingScript, $params);
        
        // 这里应该调用AI服务续写脚本
        // 暂时返回模拟数据
        return [
            'continued_content' => self::generateMockContinuation($existingScript, $params),
            'new_word_count' => self::estimateWordCount($params),
            'suggestions' => self::generateMockSuggestions($existingScript, $params)
        ];
    }

    /**
     * 构建续写提示词
     *
     * @param array $existingScript
     * @param array $params
     * @return string
     */
    private static function buildContinuePrompt(array $existingScript, array $params): string
    {
        $prompt = "动漫脚本续写提示词：\n";
        $prompt .= "你是一位动漫编剧。请根据以下现有脚本内容进行续写：\n";
        
        $prompt .= "【现有脚本】：\n{$existingScript['content']}\n\n";
        
        if (!empty($params['continue_direction'])) {
            $prompt .= "【续写方向】：{$params['continue_direction']}\n";
        }
        
        if (!empty($params['target_duration'])) {
            $prompt .= "【目标时长】：{$params['target_duration']}分钟\n";
        }

        $prompt .= "\n请续写脚本，要求：
1. 保持原有风格和人物性格
2. 情节自然发展，不突兀
3. 对话生动有趣
4. 包含适当的场景转换
5. 达到目标时长要求";

        return $prompt;
    }

    /**
     * 生成模拟续写内容
     *
     * @param array $existingScript
     * @param array $params
     * @return string
     */
    private static function generateMockContinuation(array $existingScript, array $params): string
    {
        $continueDirection = $params['continue_direction'] ?? '正常发展';
        
        $continuation = "\n\n【续写内容】\n\n";
        
        if ($continueDirection === '冲突升级') {
            $continuation .= "【场景5：夜晚的天台】\n";
            $continuation .= "时间：晚上9点\n";
            $continuation .= "地点：学校天台\n";
            $continuation .= "人物：主角、对手、神秘人\n\n";
            $continuation .= "【画面】\n";
            $continuation .= "月光下的天台，主角与对手对峙，突然出现神秘人物。\n\n";
            $continuation .= "【对话】\n";
            $continuation .= "神秘人物：你们都在这里啊...\n";
            $continuation .= "主角：你是谁？\n";
            $continuation .= "神秘人物：一个能改变你们命运的人。\n\n";
        } elseif ($continueDirection === '情感发展') {
            $continuation .= "【场景5：图书馆】\n";
            $continuation .= "时间：下午4点\n";
            $continuation .= "地点：学校图书馆\n";
            $continuation .= "人物：主角、朋友、暗恋对象\n\n";
            $continuation .= "【画面】\n";
            $continuation .= "安静的图书馆，主角在找书时偶遇暗恋对象，两人有了第一次真正的交流。\n\n";
            $continuation .= "【对话】\n";
            $continuation .= "暗恋对象：你也喜欢这本书吗？\n";
            $continuation .= "主角：（脸红）是啊，很巧你也...\n\n";
        } else {
            $continuation .= "【场景5：便利店】\n";
            $continuation .= "时间：晚上7点\n";
            $continuation .= "地点：便利店\n";
            $continuation .= "人物：主角、店员、其他顾客\n\n";
            $continuation .= "【画面】\n";
            $continuation .= "主角在便利店买东西，遇到了意想不到的人。\n\n";
            $continuation .= "【对话】\n";
            $continuation .= "店员：欢迎光临！今天有什么特别需要的吗？\n";
            $continuation .= "主角：嗯，我想找...\n\n";
        }
        
        return $continuation;
    }

    /**
     * 生成模拟建议
     *
     * @param array $existingScript
     * @param array $params
     * @return array
     */
    private static function generateMockSuggestions(array $existingScript, array $params): array
    {
        return [
            'plot_suggestions' => [
                '可以考虑加入更多角色互动',
                '增加一些意想不到的转折',
                '强化主题表达'
            ],
            'dialogue_suggestions' => [
                '对话可以更加自然流畅',
                '增加一些幽默元素',
                '注意角色语言的个性化'
            ],
            'scene_suggestions' => [
                '场景转换可以更加平滑',
                '增加更多环境描写',
                '考虑加入更多视觉元素'
            ]
        ];
    }

    /**
     * 使用AI优化脚本
     *
     * @param int $scriptId
     * @param array $params
     * @return array|false
     */
    public static function optimizeWithAI(int $scriptId, array $params): array
    {
        $existingScript = self::getById($scriptId);
        if (!$existingScript) {
            return [];
        }

        $prompt = self::buildOptimizePrompt($existingScript, $params);
        
        // 这里应该调用AI服务优化脚本
        // 暂时返回模拟数据
        return [
            'optimized_content' => self::generateMockOptimizedContent($existingScript, $params),
            'improvements' => self::generateMockImprovements($existingScript, $params),
            'quality_score' => rand(7.5, 9.5)
        ];
    }

    /**
     * 构建优化提示词
     *
     * @param array $existingScript
     * @param array $params
     * @return string
     */
    private static function buildOptimizePrompt(array $existingScript, array $params): string
    {
        $prompt = "动漫脚本优化提示词：\n";
        $prompt .= "你是一位资深动漫编剧。请优化以下脚本：\n";
        
        $prompt .= "【原始脚本】：\n{$existingScript['content']}\n\n";
        
        if (!empty($params['optimize_focus'])) {
            $prompt .= "【优化重点】：{$params['optimize_focus']}\n";
        }
        
        if (!empty($params['target_audience'])) {
            $prompt .= "【目标观众】：{$params['target_audience']}\n";
        }

        $prompt .= "\n请优化脚本，要求：
1. 保持原有故事框架和人物设定
2. 改进对话质量和流畅度
3. 优化场景描述和动作指示
4. 增强情感表达和戏剧冲突
5. 确保符合目标观众喜好
6. 提供具体的修改建议和理由";

        return $prompt;
    }

    /**
     * 生成模拟优化内容
     *
     * @param array $existingScript
     * @param array $params
     * @return string
     */
    private static function generateMockOptimizedContent(array $existingScript, array $params): string
    {
        $optimizeFocus = $params['optimize_focus'] ?? '整体优化';
        
        $optimizedContent = $existingScript['content'];
        
        if ($optimizeFocus === '对话优化') {
            $optimizedContent = str_replace('主角：（自言自语）今天一定要准时到达，不能再迟到了！',
                                       '主角：（内心独白）今天一定要准时到达，不能再迟到了！（加快脚步）',
                                       $optimizedContent);
        } elseif ($optimizeFocus === '场景优化') {
            $optimizedContent = str_replace('【画面】\n阳光透过高楼缝隙洒在街道上，行人匆匆而过。',
                                       '【画面】\n清晨的阳光透过高楼缝隙洒在繁忙的街道上，形成斑驳的光影。行人脚步匆匆，每个人脸上都带着对新一天的期待。',
                                       $optimizedContent);
        }
        
        return $optimizedContent;
    }

    /**
     * 生成模拟改进建议
     *
     * @param array $existingScript
     * @param array $params
     * @return array
     */
    private static function generateMockImprovements(array $existingScript, array $params): array
    {
        return [
            'content_improvements' => [
                '增加了更多细节描写，使场景更加生动',
                '优化了对话节奏，让角色交流更加自然',
                '强化了情感表达，增强了观众代入感'
            ],
            'structure_improvements' => [
                '调整了场景转换，使剧情更加流畅',
                '优化了冲突设置，提高了戏剧张力',
                '改进了结尾处理，让故事更加完整'
            ],
            'character_improvements' => [
                '深化了角色性格描写，使人物更加立体',
                '增加了角色互动，展现了更多人物关系',
                '优化了角色语言风格，提高了辨识度'
            ]
        ];
    }
}