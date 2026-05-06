package com.starrynight.engine.vector.store;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

/**
 * 按配置选择向量存储实现（inmemory / milvus / qdrant）。
 */
@Component
public class VectorStoreFactory {

    @Value("${vector.store.type:inmemory}")
    private String storeType;

    private final InMemoryVectorStore inMemoryStore;
    private final MilvusVectorStore milvusStore;
    private final QdrantVectorStore qdrantStore;

    public VectorStoreFactory(
            InMemoryVectorStore inMemoryStore,
            MilvusVectorStore milvusStore,
            QdrantVectorStore qdrantStore) {
        this.inMemoryStore = inMemoryStore;
        this.milvusStore = milvusStore;
        this.qdrantStore = qdrantStore;
    }

    public VectorStore getVectorStore() {
        return resolveStore(storeType);
    }

    public VectorStore getVectorStore(String storeTypeOverride) {
        if (storeTypeOverride == null || storeTypeOverride.isEmpty()) {
            return getVectorStore();
        }
        return resolveStore(storeTypeOverride);
    }

    private VectorStore resolveStore(String type) {
        if (type == null) {
            return inMemoryStore;
        }
        String t = type.toLowerCase();
        if ("milvus".equals(t)) {
            return milvusStore;
        }
        if ("qdrant".equals(t)) {
            return qdrantStore;
        }
        return inMemoryStore;
    }

    public String getCurrentStoreType() {
        return storeType;
    }
}
