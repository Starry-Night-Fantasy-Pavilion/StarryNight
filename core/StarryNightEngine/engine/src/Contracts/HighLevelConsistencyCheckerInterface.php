<?php

namespace StarryNightEngine\Contracts;

interface HighLevelConsistencyCheckerInterface
{
    /**
     * 高级一致性：语义/剧情/风格裁判（后续可接 LLM；当前默认实现可为空/占位）
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

