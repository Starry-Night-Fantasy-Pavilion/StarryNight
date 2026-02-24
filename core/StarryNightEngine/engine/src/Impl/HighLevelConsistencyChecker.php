<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 高级一致性校验器
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\HighLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\ConsistencyReport;
use StarryNightEngine\Contracts\UserTier;
use LLPhant\Chat\ChatInterface;

/**
 * 高级一致性校验器
 * 实现语义裁判，使用LLM进行深度检查
 */
final class HighLevelConsistencyChecker implements HighLevelConsistencyCheckerInterface
{
    private ChatInterface $chat;
    private array $config;
    private string $systemPrompt;

    public function __construct(
        ChatInterface $chat,
        array $config = []
    ) {
        $this->chat = $chat;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    /**
     * 高级一致性检查（语义裁判）
     */
    public function check(
        string $draft,
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): ConsistencyReport {
        // 构建高级检查提示
        $prompt = $this->buildHighLevelCheckPrompt($draft, $request, $query, $memories, $directorPlan, $tier);
        
        // 调用LLM进行语义检查
        $this->chat->setSystemMessage($this->systemPrompt);
        $response = $this->chat->generateText($prompt);
        
        // 解析检查结果
        $result = $this->parseHighLevelResponse($response);
        
        return new ConsistencyReport(
            pass: $result['pass'],
            violations: $result['violations']
        );
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
        $prompt .= "  \"violations\": [\"严重问题列表\"]\n";
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
            'violations' => $result['violations'] ?? []
        ];
    }

    /**
     * 获取默认高级检查结果
     */
    private function getDefaultHighLevelResult(): array
    {
        return [
            'pass' => true,
            'violations' => []
        ];
    }

    /**
     * 获取默认配置
     */
    private function getDefaultConfig(): array
    {
        return [
            'high_level_model' => 'gemini-pro',
            'enable_semantic_check' => true,
            'strictness' => 'medium'
        ];
    }

    /**
     * 构建系统提示
     */
    private function buildSystemPrompt(): string
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
}