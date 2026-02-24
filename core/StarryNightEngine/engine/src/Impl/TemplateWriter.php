<?php

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Contracts\WriterInterface;

/**
 * 默认写手（低级可跑版本，不调用 LLM）：
 * - 用“导演计划 + 检索记忆摘要 + 用户请求”拼装成一段可用草稿
 * - 后续接 DeepSeek/其他大模型时，替换此模块即可
 */
final class TemplateWriter implements WriterInterface
{
    public function write(
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): string {
        $style = (string)($directorPlan['style'] ?? '');
        $tone = (string)($directorPlan['tone'] ?? '');

        $lastExcerpt = is_string($request->context['last_excerpt'] ?? null) ? trim((string)$request->context['last_excerpt']) : '';

        $memorySnippets = [];
        /** @var RetrievedMemory[] $memories */
        foreach ($memories as $m) {
            $memorySnippets[] = "- {$m->id}：".trim(mb_substr($m->content, 0, 140, 'UTF-8'));
        }

        $mustInclude = $directorPlan['must_include'] ?? [];
        $mustAvoid = $directorPlan['must_avoid'] ?? [];

        $parts = [];
        if ($lastExcerpt !== '') {
            $parts[] = "（承接上文：{$lastExcerpt}）";
        }

        $parts[] = "【风格】{$style}；【情绪】{$tone}";
        $parts[] = "【用户请求】{$request->userQuery}";

        if (!empty($memorySnippets)) {
            $parts[] = "【相关记忆】\n" . implode("\n", $memorySnippets);
        }

        if (is_array($mustInclude) && count($mustInclude) > 0) {
            $parts[] = "【必须包含】" . implode('、', array_map('strval', $mustInclude));
        }
        if (is_array($mustAvoid) && count($mustAvoid) > 0) {
            $parts[] = "【必须排除】" . implode('、', array_map('strval', $mustAvoid));
        }

        // 草稿正文（先可跑，后续写手 LLM 替换）
        $body = "他（她）深吸一口气，把注意力从纷乱的念头里收拢回来。"
            . "四周的细节被重新拉近：声音、温度、光影与脚下的触感，都在提醒这一刻真实得不容逃避。"
            . "下一步要做什么并不复杂：沿着目标前进，同时牢牢记住那些不能触碰的禁忌与必须兑现的承诺。"
            . "当新的动静出现时，紧张感像弓弦一样绷紧，却仍留着一丝微弱而固执的希望。";

        return implode("\n\n", $parts) . "\n\n" . $body . "\n";
    }
}

