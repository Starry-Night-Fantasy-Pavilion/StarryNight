<?php

namespace StarryNightEngine;

use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Impl\HighLevelNoopConsistencyChecker;
use StarryNightEngine\Impl\LowLevelConsistencyChecker;
use StarryNightEngine\Impl\RuleBasedDirector;
use StarryNightEngine\Impl\RuleBasedQueryUnderstanding;
use StarryNightEngine\Impl\SimpleArrayHybridRetriever;
use StarryNightEngine\Impl\TemplateWriter;

/**
 * 先把“可跑的模块化引擎”组装起来。
 * 后续接入真实 LLM/向量库时，替换对应模块即可。
 */
final class EngineFactory
{
    /**
     * 默认工厂：完全不依赖查询理解 LLM，检索用数组语料，写手用模板。
     */
    public static function default(): StarryNightEngine
    {
        return new StarryNightEngine(
            queryUnderstanding: new RuleBasedQueryUnderstanding(),
            retriever: new SimpleArrayHybridRetriever(),
            director: new RuleBasedDirector(),
            writer: new TemplateWriter(),
            lowLevelChecker: new LowLevelConsistencyChecker(),
            highLevelChecker: new HighLevelNoopConsistencyChecker(),
        );
    }
}

