package com.starrynight.engine.vector.store;

import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.embed.EmbeddingModel;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.Optional;
import java.util.concurrent.ConcurrentHashMap;
import java.util.stream.Collectors;

/**
 * Qdrant 向量存储占位与本地联调实现：保留 host/port/apiKey 等配置项，数据路径与 {@link InMemoryVectorStore} 一致（进程内 Map）。
 * 接入真实 Qdrant 时，可在此类中替换为 io.qdrant:client 的 upsert/search 调用。
 */
@Component
public class QdrantVectorStore implements VectorStore {

    @Value("${vector.qdrant.host:localhost}")
    private String host;

    @Value("${vector.qdrant.port:6333}")
    private int port;

    @Value("${vector.qdrant.api.key:}")
    private String apiKey;

    @Value("${vector.qdrant.timeout:5000}")
    private int timeout;

    private final Map<String, Map<String, VectorEntry>> collections = new ConcurrentHashMap<>();
    private final EmbeddingModel embeddingModel;

    public QdrantVectorStore(EmbeddingModel embeddingModel) {
        this.embeddingModel = embeddingModel;
    }

    @SuppressWarnings("unused")
    public String getConfiguredHost() {
        return host;
    }

    @SuppressWarnings("unused")
    public int getConfiguredPort() {
        return port;
    }

    @SuppressWarnings("unused")
    public String getConfiguredApiKey() {
        return apiKey;
    }

    @SuppressWarnings("unused")
    public int getConfiguredTimeout() {
        return timeout;
    }

    @Override
    public void upsert(String collection, VectorEntry entry) {
        collections.computeIfAbsent(collection, k -> new ConcurrentHashMap<>()).put(entry.getId(), entry);
    }

    @Override
    public Optional<VectorEntry> get(String collection, String id) {
        Map<String, VectorEntry> c = collections.get(collection);
        if (c == null) {
            return Optional.empty();
        }
        return Optional.ofNullable(c.get(id));
    }

    @Override
    public List<VectorSearchResult> search(VectorSearchRequest request) {
        Map<String, VectorEntry> c = collections.get(request.getCollection());
        if (c == null || c.isEmpty()) {
            return List.of();
        }

        float[] q = request.getQueryVector();
        if (q == null && request.getQueryText() != null) {
            q = embeddingModel.embed(request.getQueryText());
        }
        final float[] query = q;

        List<VectorSearchResult> results = new ArrayList<>();
        for (VectorEntry e : c.values()) {
            if (request.getType() != null && (e.getMetadata() == null || e.getMetadata().getType() != request.getType())) {
                continue;
            }
            if (!request.getMetadataEquals().isEmpty()
                    && (e.getMetadata() == null || !e.getMetadata().matchesMetadataEquals(request.getMetadataEquals()))) {
                continue;
            }

            double denseScore = cosine(query, e.getDenseVector());
            double sparseScore = keywordOverlapScore(request.getQueryText(), e.getChunk());
            double score = denseScore * 0.8 + sparseScore * 0.2;

            VectorSearchResult r = new VectorSearchResult();
            r.setEntry(e);
            r.setScore(score);
            r.setDenseScore(denseScore);
            r.setSparseScore(sparseScore);
            results.add(r);
        }

        return results.stream()
                .sorted(Comparator.comparingDouble(VectorSearchResult::getScore).reversed())
                .limit(Math.max(1, request.getLimit()))
                .collect(Collectors.toList());
    }

    private double cosine(float[] a, float[] b) {
        if (a == null || b == null) {
            return 0;
        }
        int n = Math.min(a.length, b.length);
        double dot = 0;
        double na = 0;
        double nb = 0;
        for (int i = 0; i < n; i++) {
            dot += (double) a[i] * b[i];
            na += (double) a[i] * a[i];
            nb += (double) b[i] * b[i];
        }
        if (na < 1e-9 || nb < 1e-9) {
            return 0;
        }
        return dot / (Math.sqrt(na) * Math.sqrt(nb));
    }

    private double keywordOverlapScore(String q, String doc) {
        if (q == null || q.isBlank() || doc == null || doc.isBlank()) {
            return 0;
        }
        String ql = q.trim().toLowerCase();
        String d = doc.toLowerCase();
        if (ql.isEmpty()) {
            return 0;
        }
        if (!ql.contains(" ")) {
            int limit = Math.min(ql.length(), 48);
            int hit = 0;
            for (int i = 0; i < limit; i++) {
                if (d.indexOf(ql.charAt(i)) >= 0) {
                    hit++;
                }
            }
            return Math.min(1.0d, hit / (double) Math.max(8, limit));
        }
        String[] qt = ql.split("\\s+");
        int hit = 0;
        for (String t : qt) {
            if (t.isBlank()) {
                continue;
            }
            if (d.contains(t)) {
                hit++;
            }
        }
        return Math.min(1.0d, hit / (double) Math.max(1, qt.length));
    }
}
