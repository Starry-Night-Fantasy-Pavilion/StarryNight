package com.starrynight.starrynight.config;

import com.starrynight.engine.consistency.ConsistencyChecker;
import com.starrynight.engine.memcore.MemCoreManager;
import com.starrynight.engine.retrieval.HybridRetriever;
import com.starrynight.engine.token.ContextTruncator;
import com.starrynight.engine.token.SimpleTokenizer;
import com.starrynight.engine.token.Tokenizer;
import com.starrynight.engine.vector.embed.EmbeddingModel;
import com.starrynight.engine.vector.embed.HashEmbeddingModel;
import com.starrynight.engine.vector.store.InMemoryVectorStore;
import com.starrynight.engine.vector.store.MilvusVectorStore;
import com.starrynight.engine.vector.store.QdrantVectorStore;
import com.starrynight.engine.vector.store.VectorStore;
import com.starrynight.engine.vector.store.VectorStoreFactory;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.Import;

/**
 * 引擎包 {@code com.starrynight.engine} 不在主应用扫描路径内，向量相关 {@code @Component} 需显式导入。
 */
@Configuration
@Import({InMemoryVectorStore.class, MilvusVectorStore.class, QdrantVectorStore.class, VectorStoreFactory.class})
public class EngineConfig {

    @Bean
    public EmbeddingModel embeddingModel() {
        return new HashEmbeddingModel(256);
    }

    @Bean
    public VectorStore vectorStore(VectorStoreFactory vectorStoreFactory) {
        return vectorStoreFactory.getVectorStore();
    }

    @Bean
    public MemCoreManager memCoreManager(VectorStore vectorStore) {
        return new MemCoreManager(vectorStore);
    }

    @Bean
    public HybridRetriever hybridRetriever(VectorStore vectorStore) {
        return new HybridRetriever(vectorStore);
    }

    @Bean
    public ConsistencyChecker consistencyChecker() {
        return new ConsistencyChecker();
    }

    @Bean
    public Tokenizer tokenizer() {
        return new SimpleTokenizer();
    }

    @Bean
    public ContextTruncator contextTruncator(Tokenizer tokenizer, RuntimeConfigService runtimeConfigService) {
        int maxContextTokens = runtimeConfigService.getInt("starrynight.engine.max-context-tokens", 4096);
        return new ContextTruncator(tokenizer, maxContextTokens);
    }
}
