<?php

namespace StarryNightEngine\Contracts;

final class EngineResponse
{
    /**
     * @param array<string, mixed> $debug 可选：内部报告（检索结果、检查报告等）
     */
    public function __construct(
        public string $content,
        public array $debug = [],
    ) {
    }
}

