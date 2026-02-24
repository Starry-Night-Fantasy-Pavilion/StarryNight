<?php

namespace app\services;

use app\models\AIChannel;
use app\models\AIPromptTemplate;

/**
 * 小说创作AI服务类
 * 处理续写、改写、扩写、润色、大纲生成、角色生成等AI功能
 */
class NovelAIService
{
    /**
     * 调用AI生成文本
     */
    private static function callAI(string $prompt, ?string $model = null): array
    {
        try {
            // 获取可用的AI渠道
            $channels = AIChannel::all();
            $channels = array_filter($channels, function($ch) {
                return ($ch['status'] ?? '') === 'enabled';
            });

            if (empty($channels)) {
                return ['success' => false, 'error' => '没有可用的AI渠道'];
            }

            // 选择第一个可用渠道
            $channel = $channels[0];
            $baseUrl = $channel['base_url'] ?? '';
            $apiKey = $channel['api_key'] ?? '';

            if (empty($baseUrl) || empty($apiKey)) {
                return ['success' => false, 'error' => 'AI渠道配置不完整'];
            }

            // 如果没有指定模型，尝试从渠道配置中获取
            if (!$model) {
                $modelsText = $channel['models_text'] ?? '';
                if ($modelsText) {
                    $models = array_filter(array_map('trim', explode("\n", $modelsText)));
                    $model = $models[0] ?? 'gpt-3.5-turbo';
                } else {
                    $model = 'gpt-3.5-turbo';
                }
            }

            // 调用OpenAI兼容API
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
                'max_tokens' => 4000,
                'temperature' => 0.7,
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'error' => 'AI调用失败: ' . $error];
            }

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => 'AI调用失败: HTTP ' . $httpCode];
            }

            $data = json_decode($response, true);
            if (!$data || !isset($data['choices'][0]['message']['content'])) {
                return ['success' => false, 'error' => 'AI返回格式错误'];
            }

            return [
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? null
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 续写小说
     */
    public static function continueWriting(array $params): array
    {
        $context = $params['context'] ?? '';
        $characters = $params['characters'] ?? '';
        $plotRequirements = $params['plot_requirements'] ?? '';
        $style = $params['style'] ?? '';
        $wordCount = (int)($params['word_count'] ?? 500);

        $prompt = "你是一位资深小说作家。根据以下内容继续创作：\n";
        $prompt .= "【上文内容】：{$context}\n";
        if ($characters) $prompt .= "【人物设定】：{$characters}\n";
        if ($plotRequirements) $prompt .= "【情节要求】：{$plotRequirements}\n";
        if ($style) $prompt .= "【风格要求】：{$style}\n";
        $prompt .= "请继续创作 {$wordCount} 字的内容，保持风格统一，情节连贯。";

        return self::callAI($prompt);
    }

    /**
     * 改写小说
     */
    public static function rewrite(array $params): array
    {
        $content = $params['content'] ?? '';
        $requirements = $params['requirements'] ?? '';

        $prompt = "你是一位资深小说编辑。请根据以下要求改写以下内容：\n";
        $prompt .= "【原文】：{$content}\n";
        $prompt .= "【改写要求】：{$requirements}\n";
        $prompt .= "请保持原意，但按照要求进行改写。";

        return self::callAI($prompt);
    }

    /**
     * 扩写小说
     */
    public static function expand(array $params): array
    {
        $content = $params['content'] ?? '';
        $targetWords = (int)($params['target_words'] ?? 1000);
        $direction = $params['direction'] ?? '';

        $prompt = "你是一位资深小说作家。请将以下内容扩写到约 {$targetWords} 字：\n";
        $prompt .= "【原文】：{$content}\n";
        if ($direction) $prompt .= "【扩写方向】：{$direction}\n";
        $prompt .= "请保持风格统一，增加细节描写和情节展开。";

        return self::callAI($prompt);
    }

    /**
     * 润色小说
     */
    public static function polish(array $params): array
    {
        $content = $params['content'] ?? '';
        $style = $params['style'] ?? '';

        $prompt = "你是一位资深小说编辑。请对以下内容进行润色，提升文笔质量：\n";
        $prompt .= "【原文】：{$content}\n";
        if ($style) $prompt .= "【风格要求】：{$style}\n";
        $prompt .= "请保持原意和情节，但提升文字表达和可读性。";

        return self::callAI($prompt);
    }

    /**
     * 生成小说大纲
     */
    public static function generateOutline(array $params): array
    {
        $genre = $params['genre'] ?? '';
        $type = $params['type'] ?? '';
        $theme = $params['theme'] ?? '';
        $targetWords = (int)($params['target_words'] ?? 0);
        $conflict = $params['conflict'] ?? '';

        $prompt = "你是一位经验丰富的小说策划师。请根据以下要求生成小说大纲：\n";
        $prompt .= "【题材】：{$genre}\n";
        $prompt .= "【类型】：{$type}\n";
        $prompt .= "【主题】：{$theme}\n";
        $prompt .= "【目标字数】：{$targetWords}\n";
        if ($conflict) $prompt .= "【核心冲突】：{$conflict}\n";
        $prompt .= "\n请生成包含以下内容的详细大纲：\n";
        $prompt .= "1. 故事背景与世界观\n";
        $prompt .= "2. 主要角色设定（至少3个）\n";
        $prompt .= "3. 核心情节线（起承转合）\n";
        $prompt .= "4. 章节规划（每章核心事件）\n";
        $prompt .= "5. 高潮设计\n";
        $prompt .= "6. 结局方向\n\n";
        $prompt .= "要求：结构完整、逻辑清晰、冲突设置合理。\n";
        $prompt .= "请以JSON格式返回，包含上述所有内容。";

        $result = self::callAI($prompt);
        if ($result['success']) {
            // 尝试解析JSON
            $json = json_decode($result['content'], true);
            if ($json) {
                $result['outline'] = $json;
            }
        }
        return $result;
    }

    /**
     * 生成角色设定
     */
    public static function generateCharacter(array $params): array
    {
        $roleType = $params['role_type'] ?? 'other';
        $storyBackground = $params['story_background'] ?? '';
        $personalityHints = $params['personality_hints'] ?? '';
        $storyFunction = $params['story_function'] ?? '';

        $roleTypeMap = [
            'protagonist' => '主角',
            'supporting' => '配角',
            'antagonist' => '反派',
            'other' => '其他'
        ];
        $roleTypeName = $roleTypeMap[$roleType] ?? '其他';

        $prompt = "你是一位角色设计专家。请根据以下要求创建角色：\n";
        $prompt .= "【角色类型】：{$roleTypeName}\n";
        if ($storyBackground) $prompt .= "【故事背景】：{$storyBackground}\n";
        if ($personalityHints) $prompt .= "【性格特点】：{$personalityHints}\n";
        if ($storyFunction) $prompt .= "【故事作用】：{$storyFunction}\n";
        $prompt .= "\n请生成包含以下内容的角色档案：\n";
        $prompt .= "1. 基本信息：姓名、年龄、性别、职业\n";
        $prompt .= "2. 外貌描写：具体而有特色\n";
        $prompt .= "3. 性格特征：优点、缺点、矛盾点\n";
        $prompt .= "4. 背景故事：成长经历、关键事件\n";
        $prompt .= "5. 能力特长：擅长什么、弱点是什么\n";
        $prompt .= "6. 人物动机：核心追求、恐惧、价值观\n";
        $prompt .= "7. 关系网络：与其他角色的关系\n\n";
        $prompt .= "要求：立体丰满、有成长空间、符合故事逻辑。\n";
        $prompt .= "请以JSON格式返回，包含上述所有内容。";

        $result = self::callAI($prompt);
        if ($result['success']) {
            // 尝试解析JSON
            $json = json_decode($result['content'], true);
            if ($json) {
                $result['character'] = $json;
            }
        }
        return $result;
    }

    // ==================== 角色一致性检查 ====================

    /**
     * 角色一致性检查
     */
    public static function checkCharacterConsistency(array $params): array
    {
        $chapterContent = $params['chapter_content'] ?? '';
        $characterSettings = $params['character_settings'] ?? '';
        
        $prompt = "你是一位资深文学编辑。请检查以下章节中角色行为是否符合其设定：\n";
        $prompt .= "【章节内容】：{$chapterContent}\n\n";
        $prompt .= "【角色设定】：{$characterSettings}\n\n";
        $prompt .= "请分析：\n";
        $prompt .= "1. 角色行为是否符合其性格设定\n";
        $prompt .= "2. 角色对话是否符合其说话方式\n";
        $prompt .= "3. 角色情绪变化是否合理\n";
        $prompt .= "4. 是否有OOC（Out of Character）行为\n";
        $prompt .= "5. 具体的问题点和修改建议\n\n";
        $prompt .= "请给出详细的问题分析和修改建议。";
        
        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 章节分析功能 ====================

    /**
     * 章节质量评估
     */
    public static function analyzeChapter(array $params): array
    {
        $chapterContent = $params['chapter_content'] ?? '';
        $characterSettings = $params['character_settings'] ?? '';
        $plotBackground = $params['plot_background'] ?? '';

        $prompt = "你是一位资深文学编辑。请对以下章节进行专业分析：\n";
        $prompt .= "【章节内容】：{$chapterContent}\n";
        if ($characterSettings) {
            $prompt .= "【角色设定】：{$characterSettings}\n";
        }
        if ($plotBackground) {
            $prompt .= "【情节背景】：{$plotBackground}\n";
        }
        $prompt .= "\n请从以下维度分析：\n";
        $prompt .= "1. 情节评估（1-10分）\n";
        $prompt .= "   - 节奏：是否合理\n";
        $prompt .= "   - 冲突：是否突出\n";
        $prompt .= "   - 转折：是否自然\n\n";
        $prompt .= "2. 角色表现（1-10分）\n";
        $prompt .= "   - 行为合理性\n";
        $prompt .= "   - 对话质量\n";
        $prompt .= "   - 情感刻画\n\n";
        $prompt .= "3. 文笔质量（1-10分）\n";
        $prompt .= "   - 语言流畅度\n";
        $prompt .= "   - 描写生动性\n";
        $prompt .= "   - 氛围营造\n\n";
        $prompt .= "4. 具体建议\n";
        $prompt .= "   - 需要加强的地方（至少3点）\n";
        $prompt .= "   - 可以删减的内容\n";
        $prompt .= "   - 修改方向\n\n";
        $prompt .= "请给出评分（总分/各项满分）和详细分析。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 拆书仿写功能 ====================

    /**
     * 拆书分析
     */
    public static function analyzeWritingTechnique(array $params): array
    {
        $referenceText = $params['reference_text'] ?? '';

        $prompt = "你是一位文学分析专家。请分析以下文本的写作技巧：\n";
        $prompt .= "【参考文本】：{$referenceText}\n\n";
        $prompt .= "请分析：\n";
        $prompt .= "1. 写作技巧\n";
        $prompt .= "   - 叙事视角\n";
        $prompt .= "   - 描写手法\n";
        $prompt .= "   - 修辞技巧\n\n";
        $prompt .= "2. 风格特点\n";
        $prompt .= "   - 语言风格\n";
        $prompt .= "   - 节奏控制\n";
        $prompt .= "   - 氛围营造\n\n";
        $prompt .= "3. 结构特点\n";
        $prompt .= "   - 段落安排\n";
        $prompt .= "   - 信息展示顺序\n";
        $prompt .= "   - 留白技巧\n\n";
        $prompt .= "4. 仿写建议\n";
        $prompt .= "   - 可以学习的技巧\n";
        $prompt .= "   - 练习方向\n";
        $prompt .= "   - 注意事项\n";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    /**
     * 仿写生成
     */
    public static function generateImitation(array $params): array
    {
        $referenceText = $params['reference_text'] ?? '';
        $analysis = $params['analysis'] ?? '';
        $newTheme = $params['new_theme'] ?? '';
        $requirements = $params['requirements'] ?? '';
        $wordCount = (int)($params['word_count'] ?? 500);

        $prompt = "基于以下分析，请仿照该风格创作：\n";
        $prompt .= "【原文分析】：{$analysis}\n";
        $prompt .= "【原文参考】：{$referenceText}\n\n";
        $prompt .= "【仿写主题】：{$newTheme}\n";
        if ($requirements) {
            $prompt .= "【仿写要求】：{$requirements}\n";
        }
        $prompt .= "请创作约 {$wordCount} 字的内容，保持原文的风格和技巧。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 黄金开篇生成器 ====================

    /**
     * 生成黄金开篇
     */
    public static function generateOpening(array $params): array
    {
        $novelType = $params['novel_type'] ?? '';
        $coreTheme = $params['core_theme'] ?? '';
        $mainCharacter = $params['main_character'] ?? '';
        $openingAtmosphere = $params['opening_atmosphere'] ?? '';
        $wordCount = (int)($params['word_count'] ?? 500);

        $prompt = "你是一位资深小说编辑。请根据以下信息，为小说创作一个引人入胜的开篇：\n";
        $prompt .= "【小说类型】：{$novelType}\n";
        $prompt .= "【核心主题】：{$coreTheme}\n";
        $prompt .= "【主要人物】：{$mainCharacter}\n";
        $prompt .= "【开篇氛围】：{$openingAtmosphere}\n";
        $prompt .= "请创作一个约 {$wordCount} 字的开篇，要求悬念迭起，引人入胜，奠定作品基调。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 书名生成器 ====================

    /**
     * 生成书名
     */
    public static function generateTitle(array $params): array
    {
        $novelType = $params['novel_type'] ?? '';
        $coreTheme = $params['core_theme'] ?? '';
        $keywords = $params['keywords'] ?? '';
        $count = (int)($params['count'] ?? 5);

        $prompt = "你是一位爆款书名策划师。请根据以下小说信息，生成{$count}个吸引人的书名：\n";
        $prompt .= "【小说类型】：{$novelType}\n";
        $prompt .= "【核心主题】：{$coreTheme}\n";
        if ($keywords) {
            $prompt .= "【关键词】：{$keywords}\n";
        }
        $prompt .= "\n要求：简洁、有力、有记忆点，能够吸引读者眼球。\n";
        $prompt .= "请以列表形式返回书名，每个书名附上简要说明。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 简介生成器 ====================

    /**
     * 生成简介
     */
    public static function generateDescription(array $params): array
    {
        $title = $params['title'] ?? '';
        $novelType = $params['novel_type'] ?? '';
        $coreTheme = $params['core_theme'] ?? '';
        $mainCharacter = $params['main_character'] ?? '';
        $wordCount = (int)($params['word_count'] ?? 200);

        $prompt = "你是一位资深小说编辑。请为以下小说创作一个精炼吸睛的简介：\n";
        $prompt .= "【书名】：{$title}\n";
        $prompt .= "【小说类型】：{$novelType}\n";
        $prompt .= "【核心主题】：{$coreTheme}\n";
        $prompt .= "【主要人物】：{$mainCharacter}\n";
        $prompt .= "请创作一个约 {$wordCount} 字的简介，让读者欲罢不能，产生阅读冲动。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 金手指生成器 ====================

    /**
     * 生成金手指设计
     */
    public static function generateCheat(array $params): array
    {
        $novelType = $params['novel_type'] ?? '';
        $coreTheme = $params['core_theme'] ?? '';
        $mainCharacter = $params['main_character'] ?? '';

        $prompt = "你是一位创意写作专家。请为以下小说设计一个出其不意的金手指（特殊能力/设定）：\n";
        $prompt .= "【小说类型】：{$novelType}\n";
        $prompt .= "【核心主题】：{$coreTheme}\n";
        $prompt .= "【主要人物】：{$mainCharacter}\n\n";
        $prompt .= "请设计一个新颖、有趣且与故事紧密结合的金手指，包括：\n";
        $prompt .= "1. 金手指的具体设定\n";
        $prompt .= "2. 使用条件和限制\n";
        $prompt .= "3. 如何推动剧情发展\n";
        $prompt .= "4. 可能产生的戏剧冲突\n";
        $prompt .= "5. 后续升级方向\n";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 名字生成器 ====================

    /**
     * 生成名字
     */
    public static function generateName(array $params): array
    {
        $nameType = $params['name_type'] ?? 'character';
        $genre = $params['genre'] ?? '';
        $characterType = $params['character_type'] ?? '';
        $style = $params['style'] ?? '';
        $count = (int)($params['count'] ?? 10);

        $nameTypeMap = [
            'character' => '人物名字',
            'place' => '地名',
            'faction' => '势力/组织名',
            'skill' => '技能名',
            'item' => '物品名'
        ];
        $nameTypeName = $nameTypeMap[$nameType] ?? '名字';

        $prompt = "请为以下设定生成{$count}个独特而富有寓意的{$nameTypeName}：\n";
        if ($genre) {
            $prompt .= "【题材风格】：{$genre}\n";
        }
        if ($characterType) {
            $prompt .= "【人物类型】：{$characterType}\n";
        }
        if ($style) {
            $prompt .= "【风格要求】：{$style}\n";
        }
        $prompt .= "\n请以列表形式返回，每个名字附上简要寓意说明。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 封面描述生成器 ====================

    /**
     * 生成封面描述
     */
    public static function generateCoverDescription(array $params): array
    {
        $title = $params['title'] ?? '';
        $novelType = $params['novel_type'] ?? '';
        $coreTheme = $params['core_theme'] ?? '';
        $keyElements = $params['key_elements'] ?? '';

        $prompt = "请根据以下小说信息，生成一个精美的封面描述：\n";
        $prompt .= "【书名】：{$title}\n";
        $prompt .= "【小说类型】：{$novelType}\n";
        $prompt .= "【核心主题】：{$coreTheme}\n";
        if ($keyElements) {
            $prompt .= "【关键元素】：{$keyElements}\n";
        }
        $prompt .= "\n请描述一个吸引人的封面视觉设计，包括：\n";
        $prompt .= "1. 整体风格和色调\n";
        $prompt .= "2. 主要视觉元素\n";
        $prompt .= "3. 氛围和情绪\n";
        $prompt .= "4. 文字排版建议\n";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 短篇创作 ====================

    /**
     * 短篇创作
     */
    public static function writeShortStory(array $params): array
    {
        $genre = $params['genre'] ?? '';
        $theme = $params['theme'] ?? '';
        $mainCharacter = $params['main_character'] ?? '';
        $plot = $params['plot'] ?? '';
        $wordCount = (int)($params['word_count'] ?? 2000);

        $prompt = "请创作一个精彩的短篇小说：\n";
        $prompt .= "【题材类型】：{$genre}\n";
        $prompt .= "【核心主题】：{$theme}\n";
        $prompt .= "【主要人物】：{$mainCharacter}\n";
        $prompt .= "【情节梗概】：{$plot}\n";
        $prompt .= "\n请创作约 {$wordCount} 字的故事，要求情节完整，人物立体，结局有回味。";

        return self::callAI($prompt, $params['model'] ?? null);
    }

    // ==================== 短剧剧本 ====================

    /**
     * 短剧剧本创作
     */
    public static function writeShortDrama(array $params): array
    {
        $title = $params['title'] ?? '';
        $genre = $params['genre'] ?? '';
        $mainCharacter = $params['main_character'] ?? '';
        $plot = $params['plot'] ?? '';
        $episodeCount = (int)($params['episode_count'] ?? 1);

        $prompt = "请创作一个精彩的短剧剧本：\n";
        $prompt .= "【剧名】：{$title}\n";
        $prompt .= "【类型】：{$genre}\n";
        $prompt .= "【主要人物】：{$mainCharacter}\n";
        $prompt .= "【剧情梗概】：{$plot}\n";
        $prompt .= "\n请创作 {$episodeCount} 集剧本，包含：\n";
        $prompt .= "1. 场景描述\n";
        $prompt .= "2. 人物对白（标注说话人）\n";
        $prompt .= "3. 动作提示\n";
        $prompt .= "4. 情绪变化\n";
        $prompt .= "5. 每集结尾的悬念设置\n";

        return self::callAI($prompt, $params['model'] ?? null);
    }
}
