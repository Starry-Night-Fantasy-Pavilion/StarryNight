<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 混合检索服务
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Impl;

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\QueryUnderstandingResult;
use StarryNightEngine\Contracts\RetrieverInterface;
use StarryNightEngine\Contracts\RetrievedMemory;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\Services\VectorEmbeddingService;
use app\models\KnowledgeItem;

/**
 * 混合检索服务
 * 实现向量搜索、关键词搜索、元数据过滤的混合检索策略
 */
final class HybridRetriever implements RetrieverInterface
{
    private VectorEmbeddingService $vectorService;
    private int $defaultTopK;
    private float $vectorWeight;
    private float $keywordWeight;

    public function __construct(
        VectorEmbeddingService $vectorService,
        int $defaultTopK = 10,
        float $vectorWeight = 0.7,
        float $keywordWeight = 0.3
    ) {
        $this->vectorService = $vectorService;
        $this->defaultTopK = $defaultTopK;
        $this->vectorWeight = $vectorWeight;
        $this->keywordWeight = $keywordWeight;
    }

    /**
     * 执行混合检索
     */
    public function retrieve(QueryUnderstandingResult $query, EngineRequest $request, UserTier $tier): array
    {
        // 根据用户等级调整检索参数
        $topK = $this->getTopKByTier($tier);
        $searchStrategy = $this->getSearchStrategyByTier($tier);
        
        $results = [];
        
        switch ($searchStrategy) {
            case 'vector_only':
                $results = $this->vectorOnlySearch($query, $topK);
                break;
                
            case 'hybrid_basic':
                $results = $this->basicHybridSearch($query, $topK);
                break;
                
            case 'hybrid_advanced':
                $results = $this->advancedHybridSearch($query, $topK, $request);
                break;
                
            default:
                $results = $this->basicHybridSearch($query, $topK);
                break;
        }
        
        // 应用后处理过滤
        $results = $this->applyPostFilters($results, $query, $tier);
        
        return $results;
    }

    /**
     * 仅向量搜索
     */
    private function vectorOnlySearch(QueryUnderstandingResult $query, int $topK): array
    {
        $filters = $this->buildFilters($query);
        return $this->vectorService->similaritySearch($query->searchIntent, $topK, $filters);
    }

    /**
     * 基础混合搜索
     */
    private function basicHybridSearch(QueryUnderstandingResult $query, int $topK): array
    {
        $filters = $this->buildFilters($query);
        
        // 1. 向量相似性搜索
        $vectorResults = $this->vectorService->similaritySearch(
            $query->searchIntent, 
            $topK, 
            $filters
        );
        
        // 2. 关键词搜索
        $keywordResults = $this->performKeywordSearch($query, $topK);
        
        // 3. 合并和重排序
        return $this->mergeAndRerank($vectorResults, $keywordResults, $topK);
    }

    /**
     * 高级混合搜索
     */
    private function advancedHybridSearch(QueryUnderstandingResult $query, int $topK, EngineRequest $request): array
    {
        $filters = $this->buildFilters($query);
        $results = [];
        
        // 1. 多查询向量搜索
        $multiQueryResults = $this->multiQueryVectorSearch($query, $topK, $filters);
        
        // 2. 语义扩展搜索
        $semanticResults = $this->semanticExpansionSearch($query, $topK, $filters);
        
        // 3. 关键词搜索
        $keywordResults = $this->performKeywordSearch($query, $topK);
        
        // 4. 上下文感知搜索
        $contextResults = $this->contextAwareSearch($query, $request, $topK);
        
        // 5. 合并所有结果
        $allResults = array_merge($multiQueryResults, $semanticResults, $keywordResults, $contextResults);
        
        // 6. 去重和重排序
        return $this->deduplicateAndRerank($allResults, $topK);
    }

    /**
     * 多查询向量搜索
     */
    private function multiQueryVectorSearch(QueryUnderstandingResult $query, int $topK, array $filters): array
    {
        $queries = [$query->searchIntent];
        
        // 基于关键词生成变体查询
        if (!empty($query->keywords)) {
            foreach ($query->keywords as $keyword) {
                $variantQuery = str_replace($keyword, "{$keyword} 详细", $query->searchIntent);
                if ($variantQuery !== $query->searchIntent) {
                    $queries[] = $variantQuery;
                }
            }
        }
        
        $allResults = [];
        foreach ($queries as $searchQuery) {
            $results = $this->vectorService->similaritySearch($searchQuery, $topK, $filters);
            $allResults = array_merge($allResults, $results);
        }
        
        return $allResults;
    }

    /**
     * 语义扩展搜索
     */
    private function semanticExpansionSearch(QueryUnderstandingResult $query, int $topK, array $filters): array
    {
        // 基于must_include和must_avoid扩展查询
        $expandedQuery = $query->searchIntent;
        
        if (!empty($query->mustInclude)) {
            $expandedQuery .= " 包含：" . implode("、", $query->mustInclude);
        }
        
        if (!empty($query->mustAvoid)) {
            $expandedQuery .= " 排除：" . implode("、", $query->mustAvoid);
        }
        
        return $this->vectorService->similaritySearch($expandedQuery, $topK, $filters);
    }

    /**
     * 上下文感知搜索
     */
    private function contextAwareSearch(QueryUnderstandingResult $query, EngineRequest $request, int $topK): array
    {
        $context = $request->context;
        $contextualQuery = $query->searchIntent;
        
        // 添加上下文信息到查询中
        if (!empty($context)) {
            $contextParts = [];
            
            if (isset($context['characters']) && $context['characters']) {
                $contextParts[] = "角色：" . $context['characters'];
            }
            
            if (isset($context['setting']) && $context['setting']) {
                $contextParts[] = "场景：" . $context['setting'];
            }
            
            if (isset($context['plot_requirements']) && $context['plot_requirements']) {
                $contextParts[] = "情节：" . $context['plot_requirements'];
            }
            
            if (!empty($contextParts)) {
                $contextualQuery .= " 上下文：" . implode("，", $contextParts);
            }
        }
        
        $filters = $this->buildFilters($query);
        return $this->vectorService->similaritySearch($contextualQuery, $topK, $filters);
    }

    /**
     * 执行关键词搜索
     */
    private function performKeywordSearch(QueryUnderstandingResult $query, int $topK): array
    {
        $keywords = $query->keywords;
        
        if (empty($keywords)) {
            return [];
        }
        
        // 使用现有的知识库搜索功能
        $results = [];
        
        try {
            // 这里可以集成KnowledgeItem::globalSearch或其他搜索方法
            // 暂时使用简单的关键词匹配
            $searchQuery = implode(" ", $keywords);
            $items = KnowledgeItem::globalSearch(0, $searchQuery, [], $topK);
            
            foreach ($items as $item) {
                $results[] = new RetrievedMemory(
                    id: $item['id'],
                    content: $item['title'] . "\n\n" . $item['content'],
                    score: $item['relevance'] ?? 0.5,
                    meta: [
                        'source_type' => 'keyword_search',
                        'source_name' => '知识库搜索',
                        'knowledge_base_title' => $item['knowledge_base_title'] ?? ''
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log("关键词搜索失败: " . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * 构建过滤条件
     */
    private function buildFilters(QueryUnderstandingResult $query): array
    {
        $filters = [];
        
        // 添加元数据过滤
        if (!empty($query->metadata)) {
            $filters = array_merge($filters, $query->metadata);
        }
        
        // 添加must_include过滤
        if (!empty($query->mustInclude)) {
            $filters['must_include'] = $query->mustInclude;
        }
        
        // 添加must_avoid过滤
        if (!empty($query->mustAvoid)) {
            $filters['must_avoid'] = $query->mustAvoid;
        }
        
        return $filters;
    }

    /**
     * 合并和重排序结果
     */
    private function mergeAndRerank(array $vectorResults, array $keywordResults, int $topK): array
    {
        $merged = [];
        $seen = [];
        
        // 添加向量搜索结果
        foreach ($vectorResults as $result) {
            $id = $result['id'];
            if (!isset($seen[$id])) {
                $result['score'] = ($result['score'] ?? 0.5) * $this->vectorWeight;
                $merged[] = $result;
                $seen[$id] = true;
            }
        }
        
        // 添加关键词搜索结果
        foreach ($keywordResults as $result) {
            $id = $result->id;
            if (!isset($seen[$id])) {
                $resultArray = [
                    'id' => $result->id,
                    'content' => $result->content,
                    'score' => $result->score * $this->keywordWeight,
                    'meta' => $result->meta
                ];
                $merged[] = $resultArray;
                $seen[$id] = true;
            } else {
                // 如果已存在，合并分数
                foreach ($merged as &$existing) {
                    if ($existing['id'] === $id) {
                        $existing['score'] += $result->score * $this->keywordWeight;
                        break;
                    }
                }
            }
        }
        
        // 按分数排序
        usort($merged, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($merged, 0, $topK);
    }

    /**
     * 去重和重排序
     */
    private function deduplicateAndRerank(array $allResults, int $topK): array
    {
        $deduplicated = [];
        $seen = [];
        
        foreach ($allResults as $result) {
            $id = is_array($result) ? $result['id'] : $result->id;
            
            if (!isset($seen[$id])) {
                $deduplicated[] = $result;
                $seen[$id] = true;
            } else {
                // 如果已存在，增加权重
                foreach ($deduplicated as &$existing) {
                    $existingId = is_array($existing) ? $existing['id'] : $existing->id;
                    if ($existingId === $id) {
                        $existingScore = is_array($existing) ? $existing['score'] : $existing->score;
                        $newScore = is_array($result) ? $result['score'] : $result->score;
                        
                        if (is_array($existing)) {
                            $existing['score'] = min($existingScore + $newScore * 0.1, 1.0);
                        } else {
                            $existing->score = min($existingScore + $newScore * 0.1, 1.0);
                        }
                        break;
                    }
                }
            }
        }
        
        // 按分数排序
        usort($deduplicated, function($a, $b) {
            $scoreA = is_array($a) ? $a['score'] : $a->score;
            $scoreB = is_array($b) ? $b['score'] : $b->score;
            return $scoreB <=> $scoreA;
        });
        
        return array_slice($deduplicated, 0, $topK);
    }

    /**
     * 应用后处理过滤
     */
    private function applyPostFilters(array $results, QueryUnderstandingResult $query, UserTier $tier): array
    {
        $filtered = [];
        
        foreach ($results as $result) {
            $content = is_array($result) ? $result['content'] : $result->content;
            
            // 检查must_avoid
            $avoidFound = false;
            foreach ($query->mustAvoid as $avoidTerm) {
                if (stripos($content, $avoidTerm) !== false) {
                    $avoidFound = true;
                    break;
                }
            }
            
            if ($avoidFound) {
                continue;
            }
            
            // 检查must_include
            if (!empty($query->mustInclude)) {
                $includeFound = false;
                foreach ($query->mustInclude as $includeTerm) {
                    if (stripos($content, $includeTerm) !== false) {
                        $includeFound = true;
                        break;
                    }
                }
                if (!$includeFound) {
                    continue;
                }
            }
            
            $filtered[] = $result;
        }
        
        return $filtered;
    }

    /**
     * 根据用户等级获取TopK
     */
    private function getTopKByTier(UserTier $tier): int
    {
        return match($tier->value) {
            UserTier::VIP => 20,
            UserTier::REGULAR => 10,
            default => 5
        };
    }

    /**
     * 根据用户等级获取搜索策略
     */
    private function getSearchStrategyByTier(UserTier $tier): string
    {
        return match($tier->value) {
            UserTier::VIP => 'hybrid_advanced',
            UserTier::REGULAR => 'hybrid_basic',
            default => 'vector_only'
        };
    }
}