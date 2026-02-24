<?php

namespace StarryNightEngine\Contracts;

interface DirectorInterface
{
    /**
     * @param RetrievedMemory[] $memories
     * @return array<string, mixed> 统一返回结构化“导演指令”
     */
    public function plan(EngineRequest $request, QueryUnderstandingResult $query, array $memories, UserTier $tier): array;
}

