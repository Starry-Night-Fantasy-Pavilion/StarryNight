<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 低级一致性校验器
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
use StarryNightEngine\Contracts\ConsistencyReport;
use StarryNightEngine\Contracts\UserTier;

/**
 * 低级一致性校验器
 * 实现硬约束检查，不依赖LLM
 */
final class LowLevelConsistencyChecker implements LowLevelConsistencyCheckerInterface
{
    private array $config;
    private array $rules;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->rules = $this->initializeRules();
    }

    /**
     * 低级一致性检查（硬约束）
     */
    public function check(
        string $draft,
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): ConsistencyReport {
        $violations = [];
        $warnings = [];
        $score = 100;

        // 1. 长度检查
        $lengthCheck = $this->checkContentLength($draft, $directorPlan, $tier);
        if (!$lengthCheck['pass']) {
            $violations[] = $lengthCheck['message'];
            $score -= 10;
        }

        // 2. 必须包含元素检查
        $includeCheck = $this->checkMustInclude($draft, $query);
        if (!$includeCheck['pass']) {
            $violations[] = $includeCheck['message'];
            $score -= 15;
        }

        // 3. 必须避免元素检查
        $avoidCheck = $this->checkMustAvoid($draft, $query);
        if (!$avoidCheck['pass']) {
            $violations[] = $avoidCheck['message'];
            $score -= 20;
        }

        // 4. 基础连贯性检查
        $coherenceCheck = $this->checkBasicCoherence($draft);
        if (!$coherenceCheck['pass']) {
            $warnings[] = $coherenceCheck['message'];
            $score -= 5;
        }

        // 5. 格式检查
        $formatCheck = $this->checkContentFormat($draft, $request);
        if (!$formatCheck['pass']) {
            $warnings[] = $formatCheck['message'];
            $score -= 5;
        }

        // 6. 重复内容检查
        $repetitionCheck = $this->checkContentRepetition($draft);
        if (!$repetitionCheck['pass']) {
            $warnings[] = $repetitionCheck['message'];
            $score -= 8;
        }

        // 7. 敏感内容检查
        $sensitiveCheck = $this->checkSensitiveContent($draft);
        if (!$sensitiveCheck['pass']) {
            $violations[] = $sensitiveCheck['message'];
            $score -= 25;
        }

        $finalScore = max(0, $score);
        return new ConsistencyReport(
            pass: empty($violations) && $finalScore >= 70,
            violations: $violations
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
    private function checkMustInclude(string $content, QueryUnderstandingResult $query): array
    {
        $mustInclude = $query->mustInclude;
        
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
    private function checkMustAvoid(string $content, QueryUnderstandingResult $query): array
    {
        $mustAvoid = $query->mustAvoid;
        
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
     * 获取默认配置
     */
    private function getDefaultConfig(): array
    {
        return [
            'sensitive_words' => [
                '暴力', '血腥', '恐怖', '政治', '宗教极端',
                '歧视', '仇恨', '违法', '赌博', '毒品'
            ],
            'strictness' => 'medium',
            'max_content_length' => 5000
        ];
    }

    /**
     * 初始化规则
     */
    private function initializeRules(): array
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
     * 设置配置
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        if (isset($config['sensitive_words'])) {
            $this->config['sensitive_words'] = $config['sensitive_words'];
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
