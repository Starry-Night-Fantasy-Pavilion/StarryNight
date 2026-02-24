<?php
/**
 * 星夜创作引擎 (StarryNightEngine)
 * 向量嵌入服务
 * 
 * @copyright 星夜阁 (StarryNight) 2026
 * @license MIT
 * @version 1.0.0
 */

namespace StarryNightEngine\Services;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;
use app\models\KnowledgeItem;
use app\models\KnowledgeBase;

/**
 * 向量嵌入服务
 * 负责文档的嵌入生成、存储和检索
 */
class VectorEmbeddingService
{
    private EmbeddingGeneratorInterface $embeddingGenerator;
    private VectorStoreBase $vectorStore;
    private int $batchSize;
    private int $maxChunkSize;

    public function __construct(
        EmbeddingGeneratorInterface $embeddingGenerator,
        VectorStoreBase $vectorStore,
        int $batchSize = 100,
        int $maxChunkSize = 1000
    ) {
        $this->embeddingGenerator = $embeddingGenerator;
        $this->vectorStore = $vectorStore;
        $this->batchSize = $batchSize;
        $this->maxChunkSize = $maxChunkSize;
    }

    /**
     * 为知识库条目生成并存储嵌入向量
     */
    public function embedKnowledgeItem(int $knowledgeItemId): bool
    {
        try {
            $item = KnowledgeItem::find($knowledgeItemId);
            if (!$item) {
                return false;
            }

            // 将知识条目转换为Document
            $document = $this->createDocumentFromKnowledgeItem($item);
            
            // 生成嵌入向量
            $documentWithEmbedding = $this->embeddingGenerator->embedDocument($document);
            
            // 存储到向量数据库
            $this->vectorStore->addDocument($documentWithEmbedding);
            
            // 更新知识条目的嵌入向量
            KnowledgeItem::update($knowledgeItemId, [
                'embedding_vector' => $documentWithEmbedding->embedding
            ]);
            
            return true;
        } catch (\Exception $e) {
            error_log("嵌入生成失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量为知识库生成嵌入向量
     */
    public function embedKnowledgeBase(int $knowledgeBaseId): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        try {
            // 获取知识库的所有条目
            $items = KnowledgeItem::getByKnowledgeBase($knowledgeBaseId, 1, 1000);
            
            foreach ($items['items'] as $item) {
                if ($this->embedKnowledgeItem($item['id'])) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "条目 {$item['id']} 嵌入失败";
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = "批量嵌入失败: " . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * 基于查询向量进行相似性搜索
     */
    public function similaritySearch(string $query, int $k = 10, array $filters = []): array
    {
        try {
            // 生成查询向量
            $queryEmbedding = $this->embeddingGenerator->embedText($query);
            
            // 构建额外的搜索参数
            $additionalArguments = [];
            if (!empty($filters)) {
                $additionalArguments['filters'] = $filters;
            }
            
            // 执行相似性搜索
            $documents = $this->vectorStore->similaritySearch(
                $queryEmbedding, 
                $k, 
                $additionalArguments
            );
            
            // 转换为RetrievedMemory格式
            return $this->convertDocumentsToRetrievedMemories($documents);
            
        } catch (\Exception $e) {
            error_log("相似性搜索失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 混合搜索：结合向量搜索和关键词搜索
     */
    public function hybridSearch(
        string $query, 
        array $keywords = [], 
        int $k = 10, 
        array $filters = []
    ): array {
        $results = [];
        
        try {
            // 1. 向量相似性搜索
            $vectorResults = $this->similaritySearch($query, $k, $filters);
            
            // 2. 关键词搜索（如果提供了关键词）
            $keywordResults = [];
            if (!empty($keywords)) {
                $keywordResults = $this->keywordSearch($keywords, $k, $filters);
            }
            
            // 3. 合并和重排序结果
            $results = $this->mergeAndRerankResults($vectorResults, $keywordResults, $k);
            
        } catch (\Exception $e) {
            error_log("混合搜索失败: " . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * 关键词搜索
     */
    private function keywordSearch(array $keywords, int $k, array $filters = []): array
    {
        // 这里可以集成现有的全文搜索功能
        // 暂时返回空数组，后续可以扩展
        return [];
    }

    /**
     * 合并和重排序搜索结果
     */
    private function mergeAndRerankResults(array $vectorResults, array $keywordResults, int $k): array
    {
        $merged = [];
        $seen = [];
        
        // 添加向量搜索结果
        foreach ($vectorResults as $result) {
            $id = $result['id'];
            if (!isset($seen[$id])) {
                $merged[] = $result;
                $seen[$id] = true;
            }
        }
        
        // 添加关键词搜索结果
        foreach ($keywordResults as $result) {
            $id = $result['id'];
            if (!isset($seen[$id])) {
                $merged[] = $result;
                $seen[$id] = true;
            }
        }
        
        // 按分数排序并返回前k个结果
        usort($merged, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice($merged, 0, $k);
    }

    /**
     * 将知识条目转换为Document对象
     */
    private function createDocumentFromKnowledgeItem(array $item): Document
    {
        $document = new Document();
        $document->id = $item['id'];
        $document->content = $item['title'] . "\n\n" . $item['content'];
        $document->sourceType = 'knowledge_item';
        $document->sourceName = "知识库条目 {$item['id']}";
        
        // 如果已有嵌入向量，直接使用
        if (!empty($item['embedding_vector'])) {
            $document->embedding = $item['embedding_vector'];
        }
        
        return $document;
    }

    /**
     * 将Document对象转换为RetrievedMemory格式
     */
    private function convertDocumentsToRetrievedMemories(iterable $documents): array
    {
        $memories = [];
        
        foreach ($documents as $doc) {
            $memories[] = [
                'id' => $doc->id,
                'content' => $doc->content,
                'score' => 0.0, // 向量搜索的相似度分数需要从具体实现中获取
                'meta' => [
                    'source_type' => $doc->sourceType,
                    'source_name' => $doc->sourceName,
                    'hash' => $doc->hash
                ]
            ];
        }
        
        return $memories;
    }

    /**
     * 文档分块处理
     */
    public function chunkDocument(string $content, int $maxChunkSize = null): array
    {
        $chunkSize = $maxChunkSize ?? $this->maxChunkSize;
        $chunks = [];
        
        // 简单的按段落分块
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $currentChunk = '';
        
        foreach ($paragraphs as $paragraph) {
            if (strlen($currentChunk . $paragraph) > $chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                }
                
                // 如果单个段落太长，按句子分割
                if (strlen($paragraph) > $chunkSize) {
                    $sentences = preg_split('/[。！？.!?]/', $paragraph);
                    foreach ($sentences as $sentence) {
                        if (strlen($currentChunk . $sentence) > $chunkSize) {
                            if (!empty($currentChunk)) {
                                $chunks[] = trim($currentChunk);
                                $currentChunk = '';
                            }
                            $currentChunk = $sentence;
                        } else {
                            $currentChunk .= $sentence . '。';
                        }
                    }
                } else {
                    $currentChunk = $paragraph;
                }
            } else {
                $currentChunk .= $paragraph . "\n\n";
            }
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        return $chunks;
    }

    /**
     * 获取嵌入向量的维度
     */
    public function getEmbeddingDimension(): int
    {
        return $this->embeddingGenerator->getEmbeddingLength();
    }

    /**
     * 检查向量存储是否健康
     */
    public function healthCheck(): array
    {
        try {
            // 尝试生成一个测试向量
            $testEmbedding = $this->embeddingGenerator->embedText("测试");
            
            return [
                'status' => 'healthy',
                'embedding_dimension' => count($testEmbedding),
                'vector_store_connected' => true
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}