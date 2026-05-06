package com.starrynight.engine.tokusatsu;

import com.starrynight.engine.vector.VectorEntry;
import com.starrynight.engine.vector.VectorMetadata;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;
import com.starrynight.engine.vector.store.VectorStore;
import lombok.Data;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.HashMap;
import java.util.stream.Collectors;

public class CrossWorldForeshadowingManager {

    private static final String COLLECTION = "crossworld_fs";

    private final VectorStore vectorStore;
    private final Map<String, CrossWorldForeshadowing> foreshadowings;

    public CrossWorldForeshadowingManager(VectorStore vectorStore) {
        this.vectorStore = vectorStore;
        this.foreshadowings = new HashMap<>();
    }

    public void recordCrossWorldForeshadowing(CrossWorldForeshadowing foreshadowing, String sourceWorldline) {
        foreshadowing.setWorldlineId(sourceWorldline);
        foreshadowing.setCrossWorld(true);
        foreshadowing.setStatus(ForeshadowingStatus.PENDING);

        this.foreshadowings.put(foreshadowing.getId(), foreshadowing);

        VectorEntry entry = toVectorEntry(foreshadowing, sourceWorldline);
        this.vectorStore.upsert(COLLECTION, entry);
    }

    public List<WeightedForeshadowing> retrieveCrossWorldForeshadowings(String currentWorldline, SceneContext context) {
        VectorSearchRequest req = new VectorSearchRequest();
        req.setCollection(COLLECTION);
        req.setQueryVector(context != null ? context.getSemanticVector() : null);
        req.setLimit(5);
        req.setMetadataEquals(new HashMap<>(createFilter(currentWorldline)));

        List<VectorSearchResult> results = this.vectorStore.search(req);

        List<WeightedForeshadowing> weightedResults = new ArrayList<>();
        for (VectorSearchResult r : results) {
            VectorEntry entry = r.getEntry();
            if (entry == null) {
                continue;
            }
            CrossWorldForeshadowing fs = fromVectorEntry(entry);
            double similarity = calculateSimilarity(entry, context != null ? context.getSemanticVector() : null);
            double weight = similarity * 1.2;
            weightedResults.add(new WeightedForeshadowing(fs, weight));
        }

        return weightedResults.stream()
                .sorted(Comparator.comparingDouble(WeightedForeshadowing::getWeight).reversed())
                .collect(Collectors.toList());
    }

    public void markForeshadowingRecovered(String foreshadowingId) {
        CrossWorldForeshadowing fs = foreshadowings.get(foreshadowingId);
        if (fs != null) {
            fs.setStatus(ForeshadowingStatus.RECOVERED);
            fs.setRecoveredChapter(fs.getChapterNo());
        }
    }

    public List<CrossWorldForeshadowing> getPendingForeshadowings(String worldlineId) {
        return foreshadowings.values().stream()
                .filter(fs -> fs.getWorldlineId().equals(worldlineId))
                .filter(fs -> fs.getStatus() == ForeshadowingStatus.PENDING)
                .sorted(Comparator.comparingInt(CrossWorldForeshadowing::getChapterNo))
                .collect(Collectors.toList());
    }

    private Map<String, String> createFilter(String currentWorldline) {
        Map<String, String> filter = new HashMap<>();
        filter.put("worldlineId", currentWorldline);
        filter.put("status", ForeshadowingStatus.PENDING.name());
        return filter;
    }

    private double calculateSimilarity(VectorEntry entry, float[] queryVector) {
        if (entry.getDenseVector() == null || queryVector == null) {
            return 0.5;
        }
        float[] entryVector = entry.getDenseVector();
        if (entryVector.length != queryVector.length) {
            return 0.5;
        }

        double dotProduct = 0.0;
        double normA = 0.0;
        double normB = 0.0;

        for (int i = 0; i < entryVector.length; i++) {
            dotProduct += entryVector[i] * queryVector[i];
            normA += entryVector[i] * entryVector[i];
            normB += queryVector[i] * queryVector[i];
        }

        if (normA == 0 || normB == 0) {
            return 0.5;
        }

        return dotProduct / (Math.sqrt(normA) * Math.sqrt(normB));
    }

    private VectorEntry toVectorEntry(CrossWorldForeshadowing fs, String sourceWorldline) {
        VectorEntry entry = new VectorEntry();
        entry.setId(fs.getId());
        entry.setChunk(fs.getDescription());
        VectorMetadata meta = new VectorMetadata();
        meta.getExtras().put("worldlineId", sourceWorldline);
        meta.getExtras().put("isCrossWorld", "true");
        meta.getExtras().put("status", fs.getStatus() != null ? fs.getStatus().name() : ForeshadowingStatus.PENDING.name());
        entry.setMetadata(meta);
        return entry;
    }

    private CrossWorldForeshadowing fromVectorEntry(VectorEntry entry) {
        CrossWorldForeshadowing fs = new CrossWorldForeshadowing();
        fs.setId(entry.getId());
        fs.setDescription(entry.getChunk());
        Map<String, String> ex = entry.getMetadata() != null ? entry.getMetadata().getExtras() : null;
        if (ex != null) {
            fs.setWorldlineId(ex.get("worldlineId"));
            String cw = ex.get("isCrossWorld");
            fs.setCrossWorld(cw != null && Boolean.parseBoolean(cw));
            String st = ex.get("status");
            if (st != null) {
                try {
                    fs.setStatus(ForeshadowingStatus.valueOf(st));
                } catch (IllegalArgumentException ignored) {
                    fs.setStatus(ForeshadowingStatus.PENDING);
                }
            }
        }
        return fs;
    }

    @Data
    public static class CrossWorldForeshadowing {
        private String id;
        private String description;
        private String worldlineId;
        private int chapterNo;
        private boolean isCrossWorld;
        private ForeshadowingStatus status;
        private String relatedPlotPoint;
        private List<String> affectedCharacters;
        private Integer recoveredChapter;
    }

    public enum ForeshadowingStatus {
        PENDING, RECOVERED, ABANDONED
    }

    @Data
    public static class WeightedForeshadowing {
        private CrossWorldForeshadowing foreshadowing;
        private double weight;

        public WeightedForeshadowing(CrossWorldForeshadowing foreshadowing, double weight) {
            this.foreshadowing = foreshadowing;
            this.weight = weight;
        }
    }

    @Data
    public static class SceneContext {
        private float[] semanticVector;
        private String currentWorldlineId;
    }
}
