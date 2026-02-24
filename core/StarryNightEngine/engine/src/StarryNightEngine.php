<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 基于LLPhant框架优化的三层智能处理RAG系统
 *
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine;

use StarryNightEngine\Contracts\DirectorInterface;
use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\EngineResponse;
use StarryNightEngine\Contracts\HighLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\LowLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\QueryUnderstandingInterface;
use StarryNightEngine\Contracts\RetrieverInterface;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Contracts\WriterInterface;

final class StarryNightEngine
{
    public function __construct(
        private QueryUnderstandingInterface $queryUnderstanding,
        private RetrieverInterface $retriever,
        private DirectorInterface $director,
        private WriterInterface $writer,
        private LowLevelConsistencyCheckerInterface $lowLevelChecker,
        private HighLevelConsistencyCheckerInterface $highLevelChecker,
    ) {
    }

    public function generate(EngineRequest $request, UserTier $tier): EngineResponse
    {
        // 1) 低级“查询理解”（规则版）
        $q = $this->queryUnderstanding->understand($request, $tier);

        // 2) 检索
        $memories = $this->retriever->retrieve($q, $request, $tier);

        // 3) 导演规划
        $plan = $this->director->plan($request, $q, $memories, $tier);

        // 4) 写手生成草稿
        $draft = $this->writer->write($request, $q, $memories, $plan, $tier);

        // 5) 低级一致性检查（硬约束）
        $low = $this->lowLevelChecker->check($draft, $request, $q, $memories, $plan, $tier);
        if (!$low->pass) {
            return new EngineResponse(
                content: $draft,
                debug: [
                    'query' => $q,
                    'memories' => $memories,
                    'plan' => $plan,
                    'low_level' => $low,
                ],
            );
        }

        // 6) 高级一致性检查（语义裁判；默认占位为通过）
        $high = $this->highLevelChecker->check($draft, $request, $q, $memories, $plan, $tier);

        return new EngineResponse(
            content: $draft,
            debug: [
                'query' => $q,
                'memories' => $memories,
                'plan' => $plan,
                'low_level' => $low,
                'high_level' => $high,
            ],
        );
    }
}

