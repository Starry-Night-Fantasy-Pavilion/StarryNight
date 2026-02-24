<?php

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingInterface;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\UserTier;

/**
 * 低级查询理解（不调用 LLM）：
 * - 从上下文/选项中读取 must_include / must_avoid / keywords / filters
 * - 结合用户原始输入拼装 searchIntent
 * - 做一些非常轻量的归一化/去重
 */
final class RuleBasedQueryUnderstanding implements QueryUnderstandingInterface
{
    public function understand(EngineRequest $request, UserTier $tier): QueryUnderstandingResult
    {
        $ctx = $request->context;
        $opt = $request->options;

        $mustInclude = $this->collectStringList($opt, $ctx, 'must_include');
        $mustAvoid = $this->collectStringList($opt, $ctx, 'must_avoid');
        $keywords = $this->collectStringList($opt, $ctx, 'keywords');

        // 轻量“接着写/继续”消歧：若有 last_excerpt，则把它拼进意图
        $lastExcerpt = '';
        if (is_string($ctx['last_excerpt'] ?? null)) {
            $lastExcerpt = trim((string) $ctx['last_excerpt']);
        }

        // 低成本：用模板拼装 searchIntent（不做语义重写）
        $searchIntent = trim($request->userQuery);
        if ($this->looksLikeContinue($searchIntent) && $lastExcerpt !== '') {
            $searchIntent = "基于上一段结尾「{$lastExcerpt}」，继续生成：{$searchIntent}";
        }

        // VIP：默认更“宽”一些（更多关键词来自上下文）
        if ($tier->value === UserTier::VIP) {
            $keywords = array_values(array_unique(array_merge(
                $keywords,
                $this->collectStringList([], $ctx, 'entities') // 例如角色名/地名等
            )));
        }

        $metadata = [];
        if (is_array($opt['filters'] ?? null)) {
            $metadata['filters'] = $opt['filters'];
        } elseif (is_array($ctx['filters'] ?? null)) {
            $metadata['filters'] = $ctx['filters'];
        }

        return new QueryUnderstandingResult(
            searchIntent: $searchIntent,
            keywords: $this->normalizeList($keywords),
            mustInclude: $this->normalizeList($mustInclude),
            mustAvoid: $this->normalizeList($mustAvoid),
            metadata: $metadata,
        );
    }

    /**
     * @param array<string, mixed> $opt
     * @param array<string, mixed> $ctx
     * @return string[]
     */
    private function collectStringList(array $opt, array $ctx, string $key): array
    {
        $v = $opt[$key] ?? $ctx[$key] ?? [];
        if (is_string($v)) {
            $v = [$v];
        }
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * @param string[] $items
     * @return string[]
     */
    private function normalizeList(array $items): array
    {
        $items = array_values(array_filter(array_map('trim', $items), static fn ($s) => $s !== ''));
        $items = array_values(array_unique($items));
        return $items;
    }

    private function looksLikeContinue(string $q): bool
    {
        $q = trim($q);
        if ($q === '') {
            return false;
        }
        $short = mb_strlen($q, 'UTF-8') <= 6;
        $signals = ['继续', '接着写', '接下去', '续写', '往下写', '然后呢'];
        foreach ($signals as $s) {
            if (mb_strpos($q, $s, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return $short && in_array($q, ['继续', '续写', '接着写'], true);
    }
}

