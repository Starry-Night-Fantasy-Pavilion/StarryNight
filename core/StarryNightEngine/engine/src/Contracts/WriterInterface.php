<?php

namespace StarryNightEngine\Contracts;

interface WriterInterface
{
    /**
     * @param RetrievedMemory[] $memories
     * @param array<string, mixed> $directorPlan
     */
    public function write(
        EngineRequest $request,
        QueryUnderstandingResult $query,
        array $memories,
        array $directorPlan,
        UserTier $tier
    ): string;
}

