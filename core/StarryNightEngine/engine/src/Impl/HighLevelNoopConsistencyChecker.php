<?php

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\ConsistencyReport;
use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\HighLevelConsistencyCheckerInterface;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\UserTier;

/**
 * 高级一致性检查（占位实现）：
 * - 未来可接 LLM 做剧情/风格/因果链审判
 * - 当前默认直接通过，避免阻塞引擎先跑通
 */
final class HighLevelNoopConsistencyChecker implements HighLevelConsistencyCheckerInterface
{
    public function check(
        string $draft,
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): ConsistencyReport {
        return new ConsistencyReport(true, [], repairable: true);
    }
}

