<?php

namespace StarryNightEngine\Contracts;

final class QueryUnderstandingResult
{
    /**
     * @param string[] $keywords
     * @param string[] $mustInclude
     * @param string[] $mustAvoid
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $searchIntent,
        public array $keywords = [],
        public array $mustInclude = [],
        public array $mustAvoid = [],
        public array $metadata = [],
    ) {
    }
}

