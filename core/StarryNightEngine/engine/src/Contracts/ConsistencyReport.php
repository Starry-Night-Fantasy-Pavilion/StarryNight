<?php

namespace StarryNightEngine\Contracts;

final class ConsistencyReport
{
    /**
     * @param array<int, array<string, mixed>> $violations
     */
    public function __construct(
        public bool $pass,
        public array $violations = [],
        public bool $repairable = true,
    ) {
    }
}

