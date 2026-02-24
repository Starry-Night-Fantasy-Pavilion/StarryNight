<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 基于LLM的查询理解服务
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingInterface;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\UserTier;
use LLPhant\Chat\ChatInterface;

/**
 * 基于LLM的查询理解服务
 * 实现文章中描述的三层智能处理的第一层：查询理解LLM
 */
final class LLMQueryUnderstanding implements QueryUnderstandingInterface
{
    private ChatInterface $chat;
    private string $systemPrompt;

    public function __construct(ChatInterface $chat)
    {
        $this->chat = $chat;
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    public function understand(EngineRequest $request, UserTier $tier): QueryUnderstandingResult
    {
        // 构建查询理解的完整提示
        $prompt = $this->buildQueryPrompt($request, $tier);
        
        // 调用LLM进行查询理解
        $this->chat->setSystemMessage($this->systemPrompt);
        $response = $this->chat->generateText($prompt);
        
        // 解析LLM响应
        $parsed = $this->parseLLMResponse($response);
        
        // 根据用户等级调整结果
        $this->adjustForUserTier($parsed, $tier);
        
        return new QueryUnderstandingResult(
            searchIntent: $parsed['search_intent'] ?? $request->userQuery,
            keywords: $parsed['keywords'] ?? [],
            mustInclude: $parsed['must_include'] ?? [],
            mustAvoid: $parsed['must_avoid'] ?? [],
            metadata: $parsed['metadata'] ?? []
        );
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
你是一个专业的查询优化器，专门负责理解用户的创作请求并转化为适合向量检索的优化查询。

你的任务是：
1. 分析用户查询的核心意图
2. 提取关键检索词
3. 识别必须包含和必须排除的元素
4. 生成适合向量检索的完整查询描述

请严格按照以下JSON格式返回结果：
{
    "search_intent": "具体的检索意图描述",
    "keywords": ["关键词1", "关键词2", "关键词3"],
    "must_include": ["必须包含的元素1", "必须包含的元素2"],
    "must_avoid": ["必须避免的元素1", "必须避免的元素2"],
    "metadata": {
        "style": "风格要求",
        "tone": "语调要求",
        "genre": "题材类型",
        "filters": {"tag": "value"}
    }
}

注意事项：
- search_intent应该是一个完整的、语义丰富的查询描述
- keywords应该是3-5个最核心的关键词
- must_include和must_avoid用于精确控制检索范围
- metadata包含风格、语调、题材等元信息
- 对于模糊查询如"接着写"，需要结合上下文推断具体意图
PROMPT;
    }

    private function buildQueryPrompt(EngineRequest $request, UserTier $tier): string
    {
        $context = $request->context;
        $userQuery = $request->userQuery;
        
        $prompt = "请分析以下用户创作请求：\n\n";
        $prompt .= "用户查询：{$userQuery}\n\n";
        
        // 添加上下文信息
        if (!empty($context)) {
            $prompt .= "上下文信息：\n";
            
            if (isset($context['last_excerpt']) && $context['last_excerpt']) {
                $prompt .= "- 上一段结尾：" . $context['last_excerpt'] . "\n";
            }
            
            if (isset($context['characters']) && $context['characters']) {
                $prompt .= "- 角色信息：" . $context['characters'] . "\n";
            }
            
            if (isset($context['setting']) && $context['setting']) {
                $prompt .= "- 场景设定：" . $context['setting'] . "\n";
            }
            
            if (isset($context['plot_requirements']) && $context['plot_requirements']) {
                $prompt .= "- 情节要求：" . $context['plot_requirements'] . "\n";
            }
            
            if (isset($context['style']) && $context['style']) {
                $prompt .= "- 风格要求：" . $context['style'] . "\n";
            }
        }
        
        // 添加用户等级信息
        $tierName = $this->getUserTierName($tier);
        $prompt .= "\n用户等级：" . $tierName . "\n";
        
        // 添加特殊说明
        if ($tier->value === UserTier::VIP) {
            $prompt .= "（VIP用户：可以使用更高级的检索策略）\n";
        }
        
        $prompt .= "\n请返回优化后的查询信息。";
        
        return $prompt;
    }

    private function getUserTierName(UserTier $tier): string
    {
        return match($tier->value) {
            UserTier::REGULAR => '普通用户',
            UserTier::VIP => 'VIP用户',
            default => '标准用户'
        };
    }

    private function parseLLMResponse(string $response): array
    {
        // 尝试解析JSON响应
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonStr, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }
        
        // 如果JSON解析失败，使用简单的文本解析
        return $this->fallbackParse($response);
    }

    private function fallbackParse(string $response): array
    {
        $result = [
            'search_intent' => $response,
            'keywords' => [],
            'must_include' => [],
            'must_avoid' => [],
            'metadata' => []
        ];
        
        // 简单的关键词提取
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '关键词') !== false || strpos($line, 'keywords') !== false) {
                // 提取关键词
                if (preg_match('/[:：]\s*(.+)/', $line, $matches)) {
                    $keywords = preg_split('/[,，、\s]+/', $matches[1]);
                    $result['keywords'] = array_filter($keywords);
                }
            }
        }
        
        return $result;
    }

    private function adjustForUserTier(array &$parsed, UserTier $tier): void
    {
        // 根据用户等级调整查询策略
        switch ($tier->value) {
            case UserTier::REGULAR:
                // 普通用户：标准检索
                if (!isset($parsed['metadata']['filters'])) {
                    $parsed['metadata']['filters'] = [];
                }
                $parsed['metadata']['filters']['user_level'] = 'regular';
                break;
                
            case UserTier::VIP:
                // VIP用户：可以使用更多关键词，更灵活的检索
                if (count($parsed['keywords'] ?? []) < 5) {
                    // 如果关键词不够，可以添加一些默认的相关词
                    $parsed['keywords'][] = '高质量';
                    $parsed['keywords'][] = '原创';
                }
                if (!isset($parsed['metadata']['filters'])) {
                    $parsed['metadata']['filters'] = [];
                }
                $parsed['metadata']['filters']['user_level'] = 'vip';
                $parsed['metadata']['enhanced_search'] = true;
                break;
                
            default:
                // 默认：标准检索
                if (!isset($parsed['metadata']['filters'])) {
                    $parsed['metadata']['filters'] = [];
                }
                $parsed['metadata']['filters']['user_level'] = 'standard';
                break;
        }
    }
}