<?php

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\RetrieverInterface;
use StarryNightEngine\Contracts\UserTier;

/**
 * 默认检索器（低级可跑版本）：
 * - 不依赖向量库
 * - 从 context['memory_corpus']（数组）里按关键词/意图做非常轻量的“混合检索”
 * - 便于先把星夜引擎跑起来，后续可替换为真实向量数据库/LLPhant vector store
 */
final class SimpleArrayHybridRetriever implements RetrieverInterface
{
    public function retrieve(QueryUnderstandingResult $query, EngineRequest $request, UserTier $tier): array
    {
        $corpus = $request->context['memory_corpus'] ?? [];
        if (!is_array($corpus)) {
            $corpus = [];
        }

        $topK = (int)($request->options['top_k'] ?? ($tier->value === UserTier::VIP ? 12 : 6));
        if ($topK <= 0) {
            $topK = 6;
        }

        $needles = array_values(array_unique(array_filter(array_merge(
            [$query->searchIntent],
            $query->keywords,
            $query->mustInclude
        ), fn ($s) => is_string($s) && trim($s) !== '')));

        $scored = [];
        foreach ($corpus as $idx => $item) {
            // item 可是 string 或 array{id,content,meta}
            $id = is_array($item) && is_string($item['id'] ?? null) ? $item['id'] : (string)$idx;
            $content = is_array($item) && is_string($item['content'] ?? null) ? $item['content'] : (is_string($item) ? $item : '');
            $meta = is_array($item) && is_array($item['meta'] ?? null) ? $item['meta'] : [];
            if ($content === '') {
                continue;
            }

            $score = $this->score($content, $needles);
            if ($score <= 0) {
                continue;
            }

            $scored[] = new RetrievedMemory($id, $content, $score, $meta);
        }

        usort($scored, static fn (RetrievedMemory $a, RetrievedMemory $b) => $b->score <=> $a->score);
        return array_slice($scored, 0, $topK);
    }

    /**
     * @param string[] $needles
     */
    private function score(string $content, array $needles): float
    {
        $contentLower = mb_strtolower($content, 'UTF-8');
        $score = 0.0;
        foreach ($needles as $n) {
            $n = trim((string)$n);
            if ($n === '') {
                continue;
            }
            $nLower = mb_strtolower($n, 'UTF-8');
            if (mb_strpos($contentLower, $nLower, 0, 'UTF-8') !== false) {
                // 关键词命中：加分；长度越短权重越低，避免 searchIntent 过长权重过大
                $len = max(1, mb_strlen($nLower, 'UTF-8'));
                $score += 1.0 + min(2.0, 12.0 / $len);
            }
        }
        return $score;
    }
}

