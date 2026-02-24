<?php

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\DirectorInterface;
use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\UserTier;

/**
 * 默认导演（低级可跑版本，不调用 LLM）：
 * - 把约束显式化（must include / must avoid / 风格）
 * - 输出可被写手直接消费的结构化计划
 */
final class RuleBasedDirector implements DirectorInterface
{
    public function plan(EngineRequest $request, QueryUnderstandingResult $query, array $memories, UserTier $tier): array
    {
        $style = (string)($request->context['style'] ?? ($tier->value === UserTier::VIP ? '细腻、有画面感、节奏更丰富' : '清晰、连贯、节奏紧凑'));
        $tone = (string)($request->context['tone'] ?? '紧张但带希望');

        $outline = [
            '开场' => '承接上一段/上一章的最后状态，快速回忆关键事实并推进。',
            '推进' => '引入冲突或目标推进点，利用检索记忆中的设定细节增强一致性。',
            '高潮' => '解决主要冲突/形成转折，遵守 must_avoid，强化 must_include。',
            '收束' => '留钩子或自然过渡到下一段。',
        ];

        /** @var RetrievedMemory[] $memories */
        $memoryHints = array_map(
            static fn (RetrievedMemory $m) => ['id' => $m->id, 'hint' => mb_substr($m->content, 0, 120, 'UTF-8')],
            $memories
        );

        return [
            'style' => $style,
            'tone' => $tone,
            'must_include' => $query->mustInclude,
            'must_avoid' => $query->mustAvoid,
            'keywords' => $query->keywords,
            'outline' => $outline,
            'memory_hints' => $memoryHints,
            'tier' => $tier->value,
        ];
    }
}

