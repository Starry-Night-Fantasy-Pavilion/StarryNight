<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 导演规划LLM服务
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\DirectorInterface;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Services\ContextAssembler;
use LLPhant\Chat\ChatInterface;
use app\models\AIChannel;
use app\models\AIPresetModel;

/**
 * 导演规划LLM服务
 * 基于Gemini等大模型进行创作规划和指导
 */
final class LLMDirector implements DirectorInterface
{
    private ChatInterface $chat;
    private ContextAssembler $contextAssembler;
    private array $modelConfig;
    private string $systemPrompt;

    public function __construct(
        ChatInterface $chat,
        ContextAssembler $contextAssembler,
        array $modelConfig = []
    ) {
        $this->chat = $chat;
        $this->contextAssembler = $contextAssembler;
        $this->modelConfig = $modelConfig;
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    /**
     * 生成创作规划
     */
    public function plan(
        EngineRequest $request,
        QueryUnderstandingResult $queryResult,
        array $memories,
        UserTier $tier
    ): array {
        // 组装完整上下文
        $context = $this->contextAssembler->assembleContext($request, $queryResult, $memories, $tier);
        
        // 构建导演规划提示
        $prompt = $this->buildDirectorPrompt($context, $tier);
        
        // 调用LLM生成规划
        $this->chat->setSystemMessage($this->systemPrompt);
        $response = $this->chat->generateText($prompt);
        
        // 解析规划结果
        $plan = $this->parseDirectorResponse($response);
        
        // 根据用户等级调整规划
        $plan = $this->adjustPlanForUserTier($plan, $tier);
        
        return $plan;
    }

    /**
     * 构建导演系统提示
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
你是一位经验丰富的创作导演，专门负责为AI写作系统制定详细的创作规划。

你的核心职责：
1. 分析用户需求和可用资源
2. 制定结构化的创作大纲
3. 确定内容生成策略
4. 设定质量标准和约束条件
5. 提供具体的创作指导

输出格式要求：
请严格按照以下JSON格式返回规划：
{
    "plan_summary": "规划概述",
    "content_structure": {
        "introduction": "开头部分规划",
        "development": "发展部分规划", 
        "climax": "高潮部分规划",
        "conclusion": "结尾部分规划"
    },
    "writing_guidelines": {
        "style": "风格指导",
        "tone": "语调指导",
        "pace": "节奏控制",
        "detail_level": "细节程度"
    },
    "resource_utilization": {
        "primary_memories": ["主要使用的记忆ID"],
        "secondary_memories": ["次要使用的记忆ID"],
        "integration_strategy": "资源整合策略"
    },
    "quality_constraints": {
        "must_include": ["必须包含的元素"],
        "must_avoid": ["必须避免的元素"],
        "consistency_checks": ["一致性检查点"]
    },
    "generation_parameters": {
        "max_length": "最大长度",
        "creativity_level": "创意等级(1-10)",
        "focus_areas": ["重点关注领域"]
    },
    "success_criteria": {
        "content_quality": "内容质量标准",
        "user_satisfaction": "用户满意度指标",
        "technical_requirements": "技术要求"
    }
}

指导原则：
- 充分利用提供的记忆资源
- 确保内容连贯性和一致性
- 根据用户等级调整复杂度
- 平衡创意性和实用性
- 提供可执行的具体指导
PROMPT;
    }

    /**
     * 构建导演规划提示
     */
    private function buildDirectorPrompt(array $context, UserTier $tier): string
    {
        $prompt = "请为以下创作请求制定详细的导演规划：\n\n";
        
        // 用户查询
        $prompt .= "【用户查询】\n{$context['user_query']}\n\n";
        
        // 查询理解结果
        $prompt .= "【查询理解】\n";
        $prompt .= "- 搜索意图：{$context['query_understanding']['search_intent']}\n";
        $prompt .= "- 关键词：" . implode("、", $context['query_understanding']['keywords']) . "\n";
        $prompt .= "- 必须包含：" . implode("、", $context['query_understanding']['must_include']) . "\n";
        $prompt .= "- 必须避免：" . implode("、", $context['query_understanding']['must_avoid']) . "\n";
        $prompt .= "- 查询类型：{$context['query_understanding']['analysis']['query_type']}\n";
        $prompt .= "- 复杂度：{$context['query_understanding']['analysis']['intent_complexity']}\n\n";
        
        // 可用记忆资源
        $prompt .= "【可用记忆资源】\n";
        foreach ($context['retrieved_memories'] as $memory) {
            $prompt .= "- 记忆{$memory['index']} (ID:{$memory['id']}, 相关性:{$memory['relevance_score']}):\n";
            $prompt .= "  内容：{$memory['content']}\n";
            $prompt .= "  类型：{$memory['content_type']}\n";
            $prompt .= "  关键点：" . implode("；", $memory['key_points']) . "\n\n";
        }
        
        // 创作上下文
        if (!empty($context['creative_context'])) {
            $prompt .= "【创作上下文】\n";
            
            if (isset($context['creative_context']['characters'])) {
                $prompt .= "- 角色信息：{$context['creative_context']['characters']['description']}\n";
            }
            
            if (isset($context['creative_context']['setting'])) {
                $prompt .= "- 场景设定：{$context['creative_context']['setting']['description']}\n";
            }
            
            if (isset($context['creative_context']['plot'])) {
                $prompt .= "- 情节要求：{$context['creative_context']['plot']['requirements']}\n";
            }
            
            if (isset($context['creative_context']['style'])) {
                $prompt .= "- 风格要求：{$context['creative_context']['style']['description']}\n";
            }
            
            if (isset($context['creative_context']['continuation'])) {
                $prompt .= "- 续写上下文：{$context['creative_context']['continuation']['last_excerpt']}\n";
            }
            
            $prompt .= "\n";
        }
        
        // 用户偏好
        $prompt .= "【用户偏好】\n";
        $preferences = $context['user_preferences'];
        $prompt .= "- 写作风格：{$preferences['writing_style']}\n";
        $prompt .= "- 内容长度：{$preferences['content_length']}\n";
        $prompt .= "- 创意等级：{$preferences['creativity_level']}\n";
        $prompt .= "- 细节程度：{$preferences['detail_level']}\n";
        $prompt .= "- 语调偏好：{$preferences['tone_preference']}\n\n";
        
        // 用户等级信息
        $prompt .= "【用户等级】\n";
        $prompt .= "- 等级：{$tier->value}\n";
        $prompt .= "- 最大记忆数：{$context['assembly_metadata']['total_memories']}\n";
        $prompt .= "- 上下文长度限制：{$context['assembly_metadata']['context_length']}\n\n";
        
        // 特殊要求
        $prompt .= "【特殊要求】\n";
        $prompt .= "- 请根据用户等级调整规划的复杂度和详细程度\n";
        $prompt .= "- 确保规划充分利用高相关性的记忆资源\n";
        $prompt .= "- 提供具体可执行的创作指导\n";
        $prompt .= "- 设定明确的质量标准和约束条件\n\n";
        
        $prompt .= "请返回完整的导演规划JSON。";
        
        return $prompt;
    }

    /**
     * 解析导演响应
     */
    private function parseDirectorResponse(string $response): array
    {
        // 尝试解析JSON响应
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonStr, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $this->validateAndNormalizePlan($parsed);
            }
        }
        
        // 如果JSON解析失败，使用fallback解析
        return $this->fallbackParseDirectorResponse($response);
    }

    /**
     * 验证和规范化规划
     */
    private function validateAndNormalizePlan(array $plan): array
    {
        // 确保所有必需字段存在
        $normalized = [
            'plan_summary' => $plan['plan_summary'] ?? '创作规划已生成',
            'content_structure' => $this->normalizeContentStructure($plan['content_structure'] ?? []),
            'writing_guidelines' => $this->normalizeWritingGuidelines($plan['writing_guidelines'] ?? []),
            'resource_utilization' => $this->normalizeResourceUtilization($plan['resource_utilization'] ?? []),
            'quality_constraints' => $this->normalizeQualityConstraints($plan['quality_constraints'] ?? []),
            'generation_parameters' => $this->normalizeGenerationParameters($plan['generation_parameters'] ?? []),
            'success_criteria' => $this->normalizeSuccessCriteria($plan['success_criteria'] ?? [])
        ];
        
        return $normalized;
    }

    /**
     * 规范化内容结构
     */
    private function normalizeContentStructure(array $structure): array
    {
        return [
            'introduction' => $structure['introduction'] ?? '引人入胜的开头',
            'development' => $structure['development'] ?? '逻辑清晰的发展',
            'climax' => $structure['climax'] ?? '扣人心弦的高潮',
            'conclusion' => $structure['conclusion'] ?? '令人满意的结尾'
        ];
    }

    /**
     * 规范化写作指导
     */
    private function normalizeWritingGuidelines(array $guidelines): array
    {
        return [
            'style' => $guidelines['style'] ?? '自然流畅',
            'tone' => $guidelines['tone'] ?? '适中平衡',
            'pace' => $guidelines['pace'] ?? '节奏合理',
            'detail_level' => $guidelines['detail_level'] ?? '细节丰富'
        ];
    }

    /**
     * 规范化资源利用
     */
    private function normalizeResourceUtilization(array $utilization): array
    {
        return [
            'primary_memories' => $utilization['primary_memories'] ?? [],
            'secondary_memories' => $utilization['secondary_memories'] ?? [],
            'integration_strategy' => $utilization['integration_strategy'] ?? '自然融合'
        ];
    }

    /**
     * 规范化质量约束
     */
    private function normalizeQualityConstraints(array $constraints): array
    {
        return [
            'must_include' => $constraints['must_include'] ?? [],
            'must_avoid' => $constraints['must_avoid'] ?? [],
            'consistency_checks' => $constraints['consistency_checks'] ?? ['内容连贯性', '逻辑一致性']
        ];
    }

    /**
     * 规范化生成参数
     */
    private function normalizeGenerationParameters(array $parameters): array
    {
        return [
            'max_length' => $parameters['max_length'] ?? 1000,
            'creativity_level' => $parameters['creativity_level'] ?? 5,
            'focus_areas' => $parameters['focus_areas'] ?? ['内容质量', '用户满意度']
        ];
    }

    /**
     * 规范化成功标准
     */
    private function normalizeSuccessCriteria(array $criteria): array
    {
        return [
            'content_quality' => $criteria['content_quality'] ?? '高质量原创内容',
            'user_satisfaction' => $criteria['user_satisfaction'] ?? '满足用户需求',
            'technical_requirements' => $criteria['technical_requirements'] ?? '符合技术规范'
        ];
    }

    /**
     * Fallback解析导演响应
     */
    private function fallbackParseDirectorResponse(string $response): array
    {
        // 简单的文本解析，提取关键信息
        $plan = [
            'plan_summary' => substr($response, 0, 200) . '...',
            'content_structure' => [
                'introduction' => '基于用户需求的开头',
                'development' => '逻辑连贯的发展',
                'climax' => '符合主题的高潮',
                'conclusion' => '令人满意的结尾'
            ],
            'writing_guidelines' => [
                'style' => '自然流畅',
                'tone' => '适中平衡',
                'pace' => '节奏合理',
                'detail_level' => '细节丰富'
            ],
            'resource_utilization' => [
                'primary_memories' => [],
                'secondary_memories' => [],
                'integration_strategy' => '自然融合'
            ],
            'quality_constraints' => [
                'must_include' => [],
                'must_avoid' => [],
                'consistency_checks' => ['内容连贯性', '逻辑一致性']
            ],
            'generation_parameters' => [
                'max_length' => 1000,
                'creativity_level' => 5,
                'focus_areas' => ['内容质量', '用户满意度']
            ],
            'success_criteria' => [
                'content_quality' => '高质量原创内容',
                'user_satisfaction' => '满足用户需求',
                'technical_requirements' => '符合技术规范'
            ]
        ];
        
        return $plan;
    }

    /**
     * 根据用户等级调整规划
     */
    private function adjustPlanForUserTier(array $plan, UserTier $tier): array
    {
        switch ($tier->value) {
            case UserTier::VIP:
                // VIP用户：更详细的规划，更高的创意要求
                $plan['generation_parameters']['max_length'] = min($plan['generation_parameters']['max_length'], 2000);
                $plan['generation_parameters']['creativity_level'] = min($plan['generation_parameters']['creativity_level'] + 2, 10);
                $plan['writing_guidelines']['detail_level'] = '极其详细';
                $plan['success_criteria']['content_quality'] = '顶级原创内容';
                break;
                
            case UserTier::REGULAR:
                // 普通用户：标准规划
                $plan['generation_parameters']['max_length'] = min($plan['generation_parameters']['max_length'], 1000);
                $plan['generation_parameters']['creativity_level'] = min($plan['generation_parameters']['creativity_level'], 7);
                $plan['writing_guidelines']['detail_level'] = '适度详细';
                break;
                
            default:
                // 默认：基础规划
                $plan['generation_parameters']['max_length'] = min($plan['generation_parameters']['max_length'], 500);
                $plan['generation_parameters']['creativity_level'] = min($plan['generation_parameters']['creativity_level'], 5);
                $plan['writing_guidelines']['detail_level'] = '基础细节';
                break;
        }
        
        return $plan;
    }

    /**
     * 设置模型配置
     */
    public function setModelConfig(array $config): void
    {
        $this->modelConfig = $config;
        
        // 根据配置调整系统提示
        if (isset($config['model_type'])) {
            $this->systemPrompt = $this->buildSystemPromptForModel($config['model_type']);
        }
    }

    /**
     * 为特定模型构建系统提示
     */
    private function buildSystemPromptForModel(string $modelType): string
    {
        $basePrompt = $this->buildSystemPrompt();
        
        switch ($modelType) {
            case 'gemini':
                return $basePrompt . "\n\n注意：你是Gemini模型，请发挥你的多模态理解和创作能力。";
                
            case 'gpt-4':
                return $basePrompt . "\n\n注意：你是GPT-4模型，请发挥你的深度理解和创作能力。";
                
            case 'claude':
                return $basePrompt . "\n\n注意：你是Claude模型，请发挥你的创意写作和分析能力。";
                
            default:
                return $basePrompt;
        }
    }

    /**
     * 获取可用的导演模型列表
     */
    public static function getAvailableDirectorModels(): array
    {
        return [
            'gemini-pro' => [
                'name' => 'Gemini Pro',
                'description' => 'Google的多模态大模型，擅长创意写作和规划',
                'capabilities' => ['creative_writing', 'planning', 'analysis'],
                'recommended_for' => ['vip', 'regular']
            ],
            'gpt-4' => [
                'name' => 'GPT-4',
                'description' => 'OpenAI的先进模型，具有强大的理解和创作能力',
                'capabilities' => ['creative_writing', 'planning', 'analysis'],
                'recommended_for' => ['vip', 'regular']
            ],
            'claude-3-opus' => [
                'name' => 'Claude 3 Opus',
                'description' => 'Anthropic的顶级模型，擅长深度思考和创作',
                'capabilities' => ['creative_writing', 'planning', 'analysis'],
                'recommended_for' => ['vip']
            ]
        ];
    }

    /**
     * 验证模型配置
     */
    public static function validateModelConfig(array $config): array
    {
        $errors = [];
        
        if (!isset($config['model_id'])) {
            $errors[] = '缺少model_id配置';
        }
        
        if (!isset($config['api_key'])) {
            $errors[] = '缺少api_key配置';
        }
        
        if (!isset($config['base_url'])) {
            $errors[] = '缺少base_url配置';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}