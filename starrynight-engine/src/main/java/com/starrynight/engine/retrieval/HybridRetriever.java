package com.starrynight.engine.retrieval;

import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;
import com.starrynight.engine.vector.store.VectorStore;

import java.util.List;

/**
 * 混合检索入口：委托 {@link VectorStore#search}。
 * 内存实现中已在 {@code InMemoryVectorStore} 内做稠密+关键词加权；远程向量库需在对应 {@code VectorStore} 中保持等价过滤与打分策略。
 */
public class HybridRetriever {

    private final VectorStore vectorStore;

    public HybridRetriever(VectorStore vectorStore) {
        this.vectorStore = vectorStore;
    }

    public List<VectorSearchResult> search(VectorSearchRequest request) {
        return vectorStore.search(request);
    }
}

