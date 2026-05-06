package com.starrynight.engine.vector.store;

import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;

import java.util.List;
import java.util.Optional;

/**
 * 向量存储最小契约：供 MemCore、混合检索与 RAG 使用。
 * 远程实现（Milvus/Qdrant）可在对齐该接口后重新接入。
 */
public interface VectorStore {

    void upsert(String collection, VectorEntry entry);

    Optional<VectorEntry> get(String collection, String id);

    List<VectorSearchResult> search(VectorSearchRequest request);
}
