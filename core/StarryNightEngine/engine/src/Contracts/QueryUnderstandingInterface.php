<?php

namespace StarryNightEngine\Contracts;

interface QueryUnderstandingInterface
{
    public function understand(EngineRequest $request, UserTier $tier): QueryUnderstandingResult;
}

