<?php

namespace StarryNightEngine\Contracts;

interface LowLevelConsistencyCheckerInterface
{
    /**
     * 低级一致性：硬约束、可判定规则（不依赖 LLM）
     *
     * @param RetrievedMemory[] $memories
     * @param array<string, mixed> $directorPlan
     */
    public function check(
        string $draft,
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): ConsistencyReport;
}

