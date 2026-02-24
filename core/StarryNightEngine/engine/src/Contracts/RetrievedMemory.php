<?php

namespace StarryNightEngine\Contracts;

final class RetrievedMemory
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $id,
        public string $content,
        public float $score = 0.0,
        public array $meta = [],
    ) {
    }
}

