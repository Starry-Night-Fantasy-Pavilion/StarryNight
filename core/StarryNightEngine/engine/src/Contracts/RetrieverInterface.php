<?php

namespace StarryNightEngine\Contracts;

interface RetrieverInterface
{
    /**
     * @return RetrievedMemory[]
     */
    public function retrieve(QueryUnderstandingResult $query, EngineRequest $request, UserTier $tier): array;
}

