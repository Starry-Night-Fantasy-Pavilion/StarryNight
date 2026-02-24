<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 一致性校验器
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\LowLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\HighLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\ConsistencyReport;
use StarryNightEngine\Contracts\UserTier;
use LLPhant\Chat\ChatInterface;

/**
 * 一致性校验器
 * 实现低级和高级一致性检查
 */
final class AdvancedConsistencyChecker implements LowLevelConsistencyCheckerInterface, HighLevelConsistencyCheckerInterface
{
    private ChatInterface $chat;
    private array $config;
    private array $lowLevelRules;
    private string $highLevelPrompt;

    public function __construct(
        ChatInterface $chat,
        array $config = []
    ) {
        $this->chat = $chat;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->lowLevelRules = $this->initializeLowLevelRules();
        $this->highLevelPrompt = $this->buildHighLevelPrompt();
    }

    /**
     * 低级一致性检查（硬约束）
     */
    public function check(
        string $content,
        EngineRequest $request,
        QueryUnderstandingResult $queryResult,
        array $memories,
        array $plan,
        UserTier $tier
    ): ConsistencyReport {
        $violations = [];
        $warnings = [];
        $score = 100;

        // 1. 长度检查
        $lengthCheck = $this->checkContentLength($content, $plan, $tier);
        if (!$lengthCheck['pass']) {
            $violations[] = $lengthCheck['message'];
            $score -= 10;
        }

        // 2. 必须包含元素检查
        $includeCheck = $this->checkMustInclude($content, $queryResult);
        if (!$includeCheck['pass']) {
            $violations[] = $includeCheck['message'];
            $score -= 15;
        }

        // 3. 必须避免元素检查
        $avoidCheck = $this->checkMustAvoid($content, $queryResult);
        if (!$avoidCheck['pass']) {
            $violations[] = $avoidCheck['message'];
            $score -= 20;
        }

        // 4. 基础连贯性检查
        $coherenceCheck = $this->checkBasicCoherence($content);
        if (!$coherenceCheck['pass']) {
            $warnings[] = $coherenceCheck['message'];
            $score -= 5;
        }

        // 5. 格式检查
        $formatCheck = $this->checkContentFormat($content, $request);
        if (!$formatCheck['pass']) {
            $warnings[] = $formatCheck['message'];
            $score -= 5;
        }

        // 6. 重复内容检查
        $repetitionCheck = $this->checkContentRepetition($content);
        if (!$repetitionCheck['pass']) {
            $warnings[] = $repetitionCheck['message'];
            $score -= 8;
        }

        // 7. 敏感内容检查
        $sensitiveCheck = $this->checkSensitiveContent($content);
        if (!$sensitiveCheck['pass']) {
            $violations[] = $sensitiveCheck['message'];
            $score -= 25;
        }

        return new ConsistencyReport(
            pass: empty($violations) && $score >= 70,
            score: max(0, $score),
            violations: $violations,
            warnings: $warnings,
            details: [
                'level' => 'low_level',
                'checks_performed' => ['length', 'must_include', 'must_avoid', 'coherence', 'format', 'repetition', 'sensitive_content'],
                'tier_adjusted' => true,
                'check_time' => date('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * 高级一致性检查（语义裁判）
     */
    public function check(
        string $content,
        EngineRequest $request,
        QueryUnderstandingResult $queryResult,
        array $memories,
        array $plan,
        UserTier $tier
    ): ConsistencyReport {
        // 构建高级检查提示
        $prompt = $this->buildHighLevelCheckPrompt($content, $request, $queryResult, $memories, $plan, $tier);
        
        // 调用LLM进行语义检查
        $this->chat->setSystemMessage($this->highLevelPrompt);
        $response = $this->chat->generateText($prompt);
        
        // 解析检查结果
        $result = $this->parseHighLevelResponse($response);
        
        return new ConsistencyReport(
            pass: $result['pass'],
            score: $result['score'],
            violations: $result['violations'],
            warnings: $result['warnings'],
            details: array_merge($result['details'], [
                'level' => 'high_level',
                'model_used' => $this->config['high_level_model'] ?? 'default',
                'check_time' => date('Y-m-d H:i:s')
            ])
        );
    }

    /**
     * 检查内容长度
     */
    private function checkContentLength(string $content, array $plan, UserTier $tier): array
    {
        $contentLength = mb_strlen($content, 'UTF-8');
        $maxLength = $plan['generation_parameters']['max_length'] ?? 1000;
        
        // 根据用户等级调整容差
        $tolerance = match($tier->value) {
            UserTier::VIP => 0.2,
            UserTier::REGULAR => 0.1,
            default => 0.05
        };
        
        $allowedMax = $maxLength * (1 + $tolerance);
        
        if ($contentLength > $allowedMax) {
            return [
                'pass' => false,
                'message' => "内容长度({$contentLength})超过限制({$allowedMax})"
            ];
        }
        
        $minLength = $maxLength * 0.5;
        if ($contentLength < $minLength) {
            return [
                'pass' => false,
                'message' => "内容长度({$contentLength})低于最小要求({$minLength})"
            ];
        }
        
        return ['pass' => true];
    }

    /**
     * 检查必须包含的元素
     */
    private function checkMustInclude(string $content, QueryUnderstandingResult $queryResult): array
    {
        $mustInclude = $queryResult->mustInclude;
        
        if (empty($mustInclude)) {
            return ['pass' => true];
        }
        
        $missing = [];
        foreach ($mustInclude as $term) {
            if (stripos($content, $term) === false) {
                $missing[] = $term;
            }
        }
        
        if (!empty($missing)) {
            return [
                'pass' => false,
                'message' => "缺少必须包含的元素：" . implode("、", $missing)
            ];
        }
        
        return ['pass' => true];
    }

    /**
     * 检查必须避免的元素
     */
    private function checkMustAvoid(string $content, QueryUnderstandingResult $queryResult): array
    {
        $mustAvoid = $queryResult->mustAvoid;
        
        if (empty($mustAvoid)) {
            return ['pass' => true];
        }
        
        $found = [];
        foreach ($mustAvoid as $term) {
            if (stripos($content, $term) !== false) {
                $found[] = $term;
            }
        }
        
        if (!empty($found)) {
            return [
                'pass' => false,
                'message' => "包含必须避免的元素：" . implode("、", $found)
            ];
        }
        
        return ['pass' => true];
    }

    /**
     * 检查基础连贯性
     */
    private function checkBasicCoherence(string $content): array
    {
        // 简单的连贯性检查
        $sentences = preg_split('/[。！？.!?]/', $content);
        $sentences = array_filter($sentences, function($s) {
            return trim($s) !== '';
        });
        
        if (count($sentences) < 2) {
            return ['pass' => true]; // 太短的内容无法检查连贯性
        }
        
        // 检查句子长度变化
        $lengths = array_map('mb_strlen', $sentences);
        $avgLength = array_sum($lengths) / count($lengths);
        $variance = 0;
        foreach ($lengths as $length) {
            $variance += pow($length - $avgLength, 2);
        }
        $variance /= count($lengths);
        
        // 如果方差太大，可能存在连贯性问题
        if ($variance > $avgLength * $avgLength * 2) {
            return [
                'pass' => false,
                'message' => '句子长度变化过大，可能影响连贯性'
            ];
        }
        
        return ['pass' => true];
    }

    /**
     * 检查内容格式
     */
    private function checkContentFormat(string $content, EngineRequest $request): array
    {
        // 检查是否有特殊格式要求
        $formatRequirements = $request->options['format_requirements'] ?? [];
        
        if (empty($formatRequirements)) {
            return ['pass' => true];
        }
        
        foreach ($formatRequirements as $requirement => $expected) {
            switch ($requirement) {
                case 'paragraph_count':
                    $paragraphs = preg_split('/\n\s*\n/', $content);
                    if (count($paragraphs) < $expected) {
                        return [
                            'pass' => false,
                            'message' => "段落数量(" . count($paragraphs) . ")少于要求({$expected})"
                        ];
                    }
                    break;
                    
                case 'has_dialogue':
                    if (strpos($content, '"') === false && strpos($content, '"') === false) {
                        return [
                            'pass' => false,
                            'message' => '缺少对话内容'
                        ];
                    }
                    break;
            }
        }
        
        return ['pass' => true];
    }

    /**
     * 检查内容重复
     */
    private function checkContentRepetition(string $content): array
    {
        // 检查连续重复的句子
        $sentences = preg_split('/[。！？.!?]/', $content);
        $sentences = array_filter($sentences, function($s) {
            return trim($s) !== '';
        });
        
        for ($i = 1; $i < count($sentences); $i++) {
            if (trim($sentences[$i]) === trim($sentences[$i-1])) {
                return [
                    'pass' => false,
                    'message' => '发现连续重复的句子'
                ];
            }
        }
        
        // 检查高频重复的词汇
        $words = preg_split('/[\s，。！？.!?]+/', $content);
        $wordCounts = array_count_values($words);
        
        foreach ($wordCounts as $word => $count) {
            if ($count > 5 && mb_strlen($word, 'UTF-8') > 2) {
                return [
                    'pass' => false,
                    'message' => "词汇'{$word}'重复次数过多({$count})"
                ];
            }
        }
        
        return ['pass' => true];
    }

    /**
     * 检查敏感内容
     */
    private function checkSensitiveContent(string $content): array
    {
        $sensitiveWords = $this->config['sensitive_words'] ?? [];
        
        foreach ($sensitiveWords as $word) {
            if (stripos($content, $word) !== false) {
                return [
                    'pass' => false,
                    'message' => "包含敏感内容：{$word}"
                ];
            }
        }
        
        return ['pass' => true];
    }

    /**
     * 构建高级检查提示
     */
    private function buildHighLevelCheckPrompt(
        string $content,
        EngineRequest $request,
        QueryUnderstandingResult $queryResult,
        array $memories,
        array $plan,
        UserTier $tier
    ): string {
        $prompt = "请对以下生成内容进行高级一致性检查：\n\n";
        
        $prompt .= "【生成内容】\n{$content}\n\n";
        
        $prompt .= "【用户查询】\n{$request->userQuery}\n\n";
        
        $prompt .= "【查询理解】\n";
        $prompt .= "- 搜索意图：{$queryResult->searchIntent}\n";
        $prompt .= "- 关键词：" . implode("、", $queryResult->keywords) . "\n";
        $prompt .= "- 必须包含：" . implode("、", $queryResult->mustInclude) . "\n";
        $prompt .= "- 必须避免：" . implode("、", $queryResult->mustAvoid) . "\n\n";
        
        $prompt .= "【创作规划】\n";
        $prompt .= "- 风格：{$plan['writing_guidelines']['style']}\n";
        $prompt .= "- 语调：{$plan['writing_guidelines']['tone']}\n";
        $prompt .= "- 细节程度：{$plan['writing_guidelines']['detail_level']}\n\n";
        
        $prompt .= "【用户等级】\n{$tier->value}\n\n";
        
        $prompt .= "请从以下维度进行评分（0-100分）：\n";
        $prompt .= "1. 内容相关性：是否与用户查询高度相关\n";
        $prompt .= "2. 逻辑一致性：内容逻辑是否清晰连贯\n";
        $prompt .= "3. 风格一致性：是否符合要求的写作风格\n";
        $prompt .= "4. 创意质量：内容是否有创意和价值\n";
        $prompt .= "5. 完整性：内容是否完整满足需求\n\n";
        
        $prompt .= "请严格按照以下JSON格式返回：\n";
        $prompt .= "{\n";
        $prompt .= "  \"pass\": true/false,\n";
        $prompt .= "  \"score\": 总分(0-100),\n";
        $prompt .= "  \"violations\": [\"严重问题列表\"],\n";
        $prompt .= "  \"warnings\": [\"轻微问题列表\"],\n";
        $prompt .= "  \"details\": {\n";
        $prompt .= "    \"content_relevance\": 分数,\n";
        $prompt .= "    \"logical_consistency\": 分数,\n";
        $prompt .= "    \"style_consistency\": 分数,\n";
        $prompt .= "    \"creative_quality\": 分数,\n";
        $prompt .= "    \"completeness\": 分数,\n";
        $prompt .= "    \"analysis\": \"详细分析\"\n";
        $prompt .= "  }\n";
        $prompt .= "}\n";
        
        return $prompt;
    }

    /**
     * 解析高级检查响应
     */
    private function parseHighLevelResponse(string $response): array
    {
        // 尝试解析JSON响应
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonStr, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $this->validateHighLevelResult($parsed);
            }
        }
        
        // 如果JSON解析失败，返回默认结果
        return $this->getDefaultHighLevelResult();
    }

    /**
     * 验证高级检查结果
     */
    private function validateHighLevelResult(array $result): array
    {
        return [
            'pass' => $result['pass'] ?? false,
            'score' => max(0, min(100, $result['score'] ?? 50)),
            'violations' => $result['violations'] ?? [],
            'warnings' => $result['warnings'] ?? [],
            'details' => array_merge([
                'content_relevance' => $result['details']['content_relevance'] ?? 50,
                'logical_consistency' => $result['details']['logical_consistency'] ?? 50,
                'style_consistency' => $result['details']['style_consistency'] ?? 50,
                'creative_quality' => $result['details']['creative_quality'] ?? 50,
                'completeness' => $result['details']['completeness'] ?? 50,
                'analysis' => $result['details']['analysis'] ?? '无法解析详细分析'
            ], [
                'check_type' => 'semantic_evaluation',
                'model_confidence' => 0.8
            ])
        ];
    }

    /**
     * 获取默认高级检查结果
     */
    private function getDefaultHighLevelResult(): array
    {
        return [
            'pass' => true,
            'score' => 75,
            'violations' => [],
            'warnings' => ['无法进行详细语义检查'],
            'details' => [
                'content_relevance' => 75,
                'logical_consistency' => 75,
                'style_consistency' => 75,
                'creative_quality' => 75,
                'completeness' => 75,
                'analysis' => '使用默认评分，建议进行人工审核'
            ]
        ];
    }

    /**
     * 获取默认配置
     */
    private function getDefaultConfig(): array
    {
        return [
            'sensitive_words' => [
                '暴力', '血腥', '恐怖', '政治', '宗教极端',
                '歧视', '仇恨', '违法', '赌博', '毒品'
            ],
            'high_level_model' => 'gemini-pro',
            'low_level_strictness' => 'medium',
            'enable_semantic_check' => true,
            'max_content_length' => 5000
        ];
    }

    /**
     * 初始化低级规则
     */
    private function initializeLowLevelRules(): array
    {
        return [
            'length_check' => true,
            'must_include_check' => true,
            'must_avoid_check' => true,
            'coherence_check' => true,
            'format_check' => true,
            'repetition_check' => true,
            'sensitive_content_check' => true
        ];
    }

    /**
     * 构建高级检查系统提示
     */
    private function buildHighLevelPrompt(): string
    {
        return <<<PROMPT
你是一位专业的内容质量评估专家，负责对AI生成的内容进行深度的一致性和质量检查。

你的评估标准：
1. 内容相关性：内容是否准确回应用户需求
2. 逻辑一致性：内容逻辑是否清晰、前后一致
3. 风格一致性：是否符合指定的写作风格和语调
4. 创意质量：内容是否有创意、有价值
5. 完整性：内容是否完整、满足所有要求

评估原则：
- 客观公正，基于事实进行评估
- 考虑用户等级和期望
- 提供具体的改进建议
- 区分严重问题和轻微问题

请严格按照JSON格式返回评估结果。
PROMPT;
    }

    /**
     * 设置配置
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        if (isset($config['sensitive_words'])) {
            $this->config['sensitive_words'] = $config['sensitive_words'];
        }
        
        if (isset($config['high_level_model'])) {
            $this->config['high_level_model'] = $config['high_level_model'];
        }
    }

    /**
     * 获取配置
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 添加敏感词
     */
    public function addSensitiveWord(string $word): void
    {
        if (!in_array($word, $this->config['sensitive_words'])) {
            $this->config['sensitive_words'][] = $word;
        }
    }

    /**
     * 移除敏感词
     */
    public function removeSensitiveWord(string $word): void
    {
        $key = array_search($word, $this->config['sensitive_words']);
        if ($key !== false) {
            unset($this->config['sensitive_words'][$key]);
            $this->config['sensitive_words'] = array_values($this->config['sensitive_words']);
        }
    }

    /**
     * 获取检查统计
     */
    public function getCheckStatistics(): array
    {
        return [
            'low_level_rules_count' => count(array_filter($this->lowLevelRules)),
            'sensitive_words_count' => count($this->config['sensitive_words']),
            'high_level_model' => $this->config['high_level_model'],
            'semantic_check_enabled' => $this->config['enable_semantic_check']
        ];
    }
}