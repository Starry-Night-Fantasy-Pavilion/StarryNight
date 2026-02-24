<?php

namespace StarryNightEngine\Contracts;

final class EngineRequest
{
    /**
     * @param array<string, mixed> $context 例如：对话历史、章节状态、角色表、风格要求等
     * @param array<string, mixed> $options 例如：topK、maxTokens、strictness 等
     */
    public function __construct(
        public string $userQuery,
        public array $context = [],
        public array $options = [],
    ) {
    }
}

