<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 上下文组装器
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Services;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\UserTier;

/**
 * 上下文组装器
 * 负责将检索结果与历史上下文组合，为导演规划提供完整的上下文信息
 */
class ContextAssembler
{
    private int $maxContextLength;
    private int $maxMemories;
    private array $contextTemplates;

    public function __construct(
        int $maxContextLength = 8000,
        int $maxMemories = 10
    ) {
        $this->maxContextLength = $maxContextLength;
        $this->maxMemories = $maxMemories;
        $this->contextTemplates = $this->initializeTemplates();
    }

    /**
     * 组装完整上下文
     */
    public function assembleContext(
        EngineRequest $request,
        QueryUnderstandingResult $queryResult,
        array $memories,
        UserTier $tier
    ): array {
        $context = [
            'user_query' => $request->userQuery,
            'query_understanding' => $this->formatQueryResult($queryResult),
            'retrieved_memories' => $this->formatMemories($memories, $tier),
            'conversation_history' => $this->extractConversationHistory($request),
            'creative_context' => $this->extractCreativeContext($request),
            'user_preferences' => $this->extractUserPreferences($request, $tier),
            'assembly_metadata' => [
                'total_memories' => count($memories),
                'context_length' => 0,
                'assembly_time' => date('Y-m-d H:i:s'),
                'user_tier' => $tier->value
            ]
        ];

        // 优化上下文长度
        $context = $this->optimizeContextLength($context, $tier);
        
        // 添加上下文摘要
        $context['context_summary'] = $this->generateContextSummary($context);
        
        return $context;
    }

    /**
     * 格式化查询理解结果
     */
    private function formatQueryResult(QueryUnderstandingResult $queryResult): array
    {
        return [
            'search_intent' => $queryResult->searchIntent,
            'keywords' => $queryResult->keywords,
            'must_include' => $queryResult->mustInclude,
            'must_avoid' => $queryResult->mustAvoid,
            'metadata' => $queryResult->metadata,
            'analysis' => [
                'intent_complexity' => $this->analyzeIntentComplexity($queryResult),
                'query_type' => $this->classifyQueryType($queryResult),
                'domain_specificity' => $this->assessDomainSpecificity($queryResult)
            ]
        ];
    }

    /**
     * 格式化检索记忆
     */
    private function formatMemories(array $memories, UserTier $tier): array
    {
        $formattedMemories = [];
        $maxMemories = $this->getMaxMemoriesByTier($tier);
        
        // 按分数排序
        usort($memories, function($a, $b) {
            $scoreA = is_array($a) ? $a['score'] : $a->score;
            $scoreB = is_array($b) ? $b['score'] : $b->score;
            return $scoreB <=> $scoreA;
        });

        $memories = array_slice($memories, 0, $maxMemories);

        foreach ($memories as $index => $memory) {
            $memoryArray = is_array($memory) ? $memory : [
                'id' => $memory->id,
                'content' => $memory->content,
                'score' => $memory->score,
                'meta' => $memory->meta
            ];

            $formattedMemories[] = [
                'index' => $index + 1,
                'id' => $memoryArray['id'],
                'content' => $this->truncateContent($memoryArray['content']),
                'relevance_score' => $memoryArray['score'],
                'source_info' => $memoryArray['meta'] ?? [],
                'content_type' => $this->classifyContentType($memoryArray['content']),
                'key_points' => $this->extractKeyPoints($memoryArray['content']),
                'usage_suggestion' => $this->suggestUsage($memoryArray, $index)
            ];
        }

        return $formattedMemories;
    }

    /**
     * 提取对话历史
     */
    private function extractConversationHistory(EngineRequest $request): array
    {
        $context = $request->context;
        $history = [];

        if (isset($context['conversation_history']) && is_array($context['conversation_history'])) {
            $history = $context['conversation_history'];
            
            // 只保留最近的对话轮次
            $maxHistory = 5;
            if (count($history) > $maxHistory) {
                $history = array_slice($history, -$maxHistory);
            }
        }

        return [
            'messages' => $history,
            'summary' => $this->summarizeConversation($history),
            'context_continuity' => $this->assessContextContinuity($history)
        ];
    }

    /**
     * 提取创作上下文
     */
    private function extractCreativeContext(EngineRequest $request): array
    {
        $context = $request->context;
        $creativeContext = [];

        // 提取角色信息
        if (isset($context['characters'])) {
            $creativeContext['characters'] = [
                'description' => $context['characters'],
                'analysis' => $this->analyzeCharacters($context['characters'])
            ];
        }

        // 提取场景设定
        if (isset($context['setting'])) {
            $creativeContext['setting'] = [
                'description' => $context['setting'],
                'elements' => $this->extractSettingElements($context['setting'])
            ];
        }

        // 提取情节要求
        if (isset($context['plot_requirements'])) {
            $creativeContext['plot'] = [
                'requirements' => $context['plot_requirements'],
                'structure' => $this->analyzePlotStructure($context['plot_requirements'])
            ];
        }

        // 提取风格要求
        if (isset($context['style'])) {
            $creativeContext['style'] = [
                'description' => $context['style'],
                'elements' => $this->extractStyleElements($context['style'])
            ];
        }

        // 提取上一段内容
        if (isset($context['last_excerpt'])) {
            $creativeContext['continuation'] = [
                'last_excerpt' => $context['last_excerpt'],
                'continuation_points' => $this->identifyContinuationPoints($context['last_excerpt'])
            ];
        }

        return $creativeContext;
    }

    /**
     * 提取用户偏好
     */
    private function extractUserPreferences(EngineRequest $request, UserTier $tier): array
    {
        $context = $request->context;
        $preferences = [];

        // 从上下文中提取偏好
        if (isset($context['preferences'])) {
            $preferences = array_merge($preferences, $context['preferences']);
        }

        // 从选项中提取偏好
        if (isset($request->options['preferences'])) {
            $preferences = array_merge($preferences, $request->options['preferences']);
        }

        // 根据用户等级设置默认偏好
        $preferences = array_merge($this->getDefaultPreferencesByTier($tier), $preferences);

        return [
            'writing_style' => $preferences['writing_style'] ?? 'balanced',
            'content_length' => $preferences['content_length'] ?? 'medium',
            'creativity_level' => $preferences['creativity_level'] ?? 'moderate',
            'detail_level' => $preferences['detail_level'] ?? 'standard',
            'tone_preference' => $preferences['tone_preference'] ?? 'neutral',
            'genre_preferences' => $preferences['genre_preferences'] ?? [],
            'avoidance_list' => $preferences['avoidance_list'] ?? []
        ];
    }

    /**
     * 优化上下文长度
     */
    private function optimizeContextLength(array $context, UserTier $tier): array
    {
        $maxLength = $this->getMaxContextLengthByTier($tier);
        
        // 计算当前上下文长度
        $currentLength = $this->calculateContextLength($context);
        
        if ($currentLength <= $maxLength) {
            return $context;
        }

        // 按优先级裁剪内容
        $context = $this->trimByPriority($context, $maxLength);
        
        // 更新元数据
        $context['assembly_metadata']['context_length'] = $this->calculateContextLength($context);
        $context['assembly_metadata']['trimmed'] = true;

        return $context;
    }

    /**
     * 生成上下文摘要
     */
    private function generateContextSummary(array $context): array
    {
        return [
            'query_focus' => $this->summarizeQueryFocus($context),
            'available_resources' => $this->summarizeAvailableResources($context),
            'creative_constraints' => $this->summarizeConstraints($context),
            'generation_goals' => $this->identifyGenerationGoals($context),
            'key_context_elements' => $this->extractKeyContextElements($context)
        ];
    }

    // 辅助方法实现

    private function initializeTemplates(): array
    {
        return [
            'memory_template' => "记忆 {index} (相关性: {score}): {content}",
            'context_header' => "=== 创作上下文 ===",
            'memory_header' => "=== 相关记忆 ===",
            'history_header' => "=== 对话历史 ==="
        ];
    }

    private function analyzeIntentComplexity(QueryUnderstandingResult $queryResult): string
    {
        $complexity = 0;
        
        if (count($queryResult->keywords) > 3) $complexity++;
        if (count($queryResult->mustInclude) > 2) $complexity++;
        if (count($queryResult->mustAvoid) > 1) $complexity++;
        if (!empty($queryResult->metadata)) $complexity++;
        
        return match($complexity) {
            0, 1 => 'simple',
            2, 3 => 'moderate',
            default => 'complex'
        };
    }

    private function classifyQueryType(QueryUnderstandingResult $queryResult): string
    {
        $intent = strtolower($queryResult->searchIntent);
        
        if (strpos($intent, '继续') !== false || strpos($intent, '接着') !== false) {
            return 'continuation';
        }
        
        if (strpos($intent, '角色') !== false || strpos($intent, '人物') !== false) {
            return 'character';
        }
        
        if (strpos($intent, '情节') !== false || strpos($intent, '故事') !== false) {
            return 'plot';
        }
        
        if (strpos($intent, '场景') !== false || strpos($intent, '环境') !== false) {
            return 'setting';
        }
        
        return 'general';
    }

    private function assessDomainSpecificity(QueryUnderstandingResult $queryResult): string
    {
        $specificity = 0;
        
        if (!empty($queryResult->mustInclude)) $specificity += 2;
        if (!empty($queryResult->mustAvoid)) $specificity += 1;
        if (count($queryResult->keywords) > 2) $specificity += 1;
        
        return match($specificity) {
            0, 1 => 'general',
            2, 3 => 'specific',
            default => 'highly_specific'
        };
    }

    private function getMaxMemoriesByTier(UserTier $tier): int
    {
        return match($tier->value) {
            UserTier::VIP => 15,
            UserTier::REGULAR => 10,
            default => 5
        };
    }

    private function getMaxContextLengthByTier(UserTier $tier): int
    {
        return match($tier->value) {
            UserTier::VIP => 12000,
            UserTier::REGULAR => 8000,
            default => 4000
        };
    }

    private function truncateContent(string $content, int $maxLength = 500): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        return substr($content, 0, $maxLength) . '...';
    }

    private function classifyContentType(string $content): string
    {
        if (strpos($content, '角色') !== false || strpos($content, '人物') !== false) {
            return 'character_info';
        }
        
        if (strpos($content, '场景') !== false || strpos($content, '环境') !== false) {
            return 'setting_description';
        }
        
        if (strpos($content, '情节') !== false || strpos($content, '故事') !== false) {
            return 'plot_element';
        }
        
        return 'general_content';
    }

    private function extractKeyPoints(string $content): array
    {
        // 简单的关键点提取
        $sentences = preg_split('/[。！？.!?]/', $content);
        $keyPoints = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 10 && strlen($sentence) < 100) {
                $keyPoints[] = $sentence;
            }
        }
        
        return array_slice($keyPoints, 0, 3);
    }

    private function suggestUsage(array $memory, int $index): string
    {
        $score = $memory['score'];
        
        if ($score > 0.8) {
            return 'high_relevance_reference';
        } elseif ($score > 0.6) {
            return 'contextual_reference';
        } elseif ($score > 0.4) {
            return 'inspiration_source';
        } else {
            return 'background_reference';
        }
    }

    private function summarizeConversation(array $history): string
    {
        if (empty($history)) {
            return '无对话历史';
        }
        
        $lastMessage = end($history);
        return "最近讨论: " . substr($lastMessage['content'] ?? '', 0, 50) . '...';
    }

    private function assessContextContinuity(array $history): string
    {
        if (count($history) < 2) {
            return 'insufficient_data';
        }
        
        // 简单的连续性检查
        return 'continuous';
    }

    private function analyzeCharacters(string $characters): array
    {
        return [
            'count' => substr_count($characters, '，') + 1,
            'complexity' => strlen($characters) > 100 ? 'high' : 'medium'
        ];
    }

    private function extractSettingElements(string $setting): array
    {
        $elements = [];
        
        if (strpos($setting, '森林') !== false) $elements[] = 'forest';
        if (strpos($setting, '城市') !== false) $elements[] = 'city';
        if (strpos($setting, '海洋') !== false) $elements[] = 'ocean';
        
        return $elements;
    }

    private function analyzePlotStructure(string $plot): array
    {
        return [
            'has_conflict' => strpos($plot, '冲突') !== false,
            'has_resolution' => strpos($plot, '解决') !== false,
            'complexity' => strlen($plot) > 200 ? 'complex' : 'simple'
        ];
    }

    private function extractStyleElements(string $style): array
    {
        $elements = [];
        
        if (strpos($style, '幽默') !== false) $elements[] = 'humor';
        if (strpos($style, '严肃') !== false) $elements[] = 'serious';
        if (strpos($style, '抒情') !== false) $elements[] = 'lyrical';
        
        return $elements;
    }

    private function identifyContinuationPoints(string $excerpt): array
    {
        $points = [];
        
        // 查找可能的续写点
        if (preg_match('/([。！？])\s*$/', $excerpt, $matches)) {
            $points[] = 'sentence_end';
        }
        
        return $points;
    }

    private function getDefaultPreferencesByTier(UserTier $tier): array
    {
        return match($tier->value) {
            UserTier::VIP => [
                'writing_style' => 'creative',
                'content_length' => 'long',
                'creativity_level' => 'high',
                'detail_level' => 'detailed'
            ],
            UserTier::REGULAR => [
                'writing_style' => 'balanced',
                'content_length' => 'medium',
                'creativity_level' => 'moderate',
                'detail_level' => 'standard'
            ],
            default => [
                'writing_style' => 'simple',
                'content_length' => 'short',
                'creativity_level' => 'low',
                'detail_level' => 'basic'
            ]
        };
    }

    private function calculateContextLength(array $context): int
    {
        return strlen(json_encode($context, JSON_UNESCAPED_UNICODE));
    }

    private function trimByPriority(array $context, int $maxLength): array
    {
        // 按优先级裁剪：保留核心查询和高质量记忆
        if (isset($context['retrieved_memories'])) {
            $context['retrieved_memories'] = array_slice($context['retrieved_memories'], 0, 5);
        }
        
        return $context;
    }

    private function summarizeQueryFocus(array $context): string
    {
        $query = $context['query_understanding']['search_intent'] ?? '';
        return substr($query, 0, 100);
    }

    private function summarizeAvailableResources(array $context): array
    {
        $memories = $context['retrieved_memories'] ?? [];
        return [
            'memory_count' => count($memories),
            'high_relevance_count' => count(array_filter($memories, fn($m) => $m['relevance_score'] > 0.7)),
            'content_types' => array_unique(array_column($memories, 'content_type'))
        ];
    }

    private function summarizeConstraints(array $context): array
    {
        $query = $context['query_understanding'] ?? [];
        return [
            'must_include' => $query['must_include'] ?? [],
            'must_avoid' => $query['must_avoid'] ?? [],
            'style_requirements' => $context['creative_context']['style']['description'] ?? ''
        ];
    }

    private function identifyGenerationGoals(array $context): array
    {
        $goals = [];
        
        $queryType = $context['query_understanding']['analysis']['query_type'] ?? 'general';
        $goals[] = "generate_{$queryType}_content";
        
        if (isset($context['creative_context']['continuation'])) {
            $goals[] = 'continue_narrative';
        }
        
        return $goals;
    }

    private function extractKeyContextElements(array $context): array
    {
        $elements = [];
        
        if (isset($context['creative_context']['characters'])) {
            $elements[] = 'character_context';
        }
        
        if (isset($context['creative_context']['setting'])) {
            $elements[] = 'setting_context';
        }
        
        if (isset($context['creative_context']['plot'])) {
            $elements[] = 'plot_context';
        }
        
        return $elements;
    }
}