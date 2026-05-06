package com.starrynight.engine.rag;

import com.starrynight.engine.vector.embed.EmbeddingModel;
import com.starrynight.engine.vector.search.VectorSearchRequest;
import com.starrynight.engine.vector.search.VectorSearchResult;
import com.starrynight.engine.vector.store.VectorStore;
import lombok.Data;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

@Component
public class RAGRetrieval {

    @Autowired
    private VectorStore vectorStore;

    @Autowired
    private EmbeddingModel embeddingModel;

    private static final int DEFAULT_MAX_TOKENS = 500;
    private static final int DEFAULT_OVERLAP_TOKENS = 50;
    private static final int DEFAULT_TOP_K = 5;
    private static final float DEFAULT_SIMILARITY_THRESHOLD = 0.7f;

    private boolean rerankEnabled = true;

    public List<KnowledgeChunk> chunkDocument(Document document) {
        return chunkDocument(document, DEFAULT_MAX_TOKENS, DEFAULT_OVERLAP_TOKENS);
    }

    public List<KnowledgeChunk> chunkDocument(Document document, int maxTokens, int overlapTokens) {
        List<KnowledgeChunk> chunks = new ArrayList<>();

        String text = document.getContent();
        List<String> semanticChunks = semanticSplit(text, maxTokens, overlapTokens);

        for (int i = 0; i < semanticChunks.size(); i++) {
            String chunkText = semanticChunks.get(i);
            float[] embedding = embeddingModel.embed(chunkText);

            KnowledgeChunk chunk = new KnowledgeChunk();
            chunk.setId(UUID.randomUUID().toString());
            chunk.setDocumentId(document.getId());
            chunk.setContent(chunkText);
            chunk.setMetadata(createMetadata(document, i));
            chunk.setVector(embedding);
            chunk.setTokenCount(estimateTokenCount(chunkText));
            chunk.setCreatedAt(LocalDateTime.now());
            chunk.setStatus(KnowledgeChunk.ChunkStatus.ACTIVE);

            chunks.add(chunk);
        }

        return chunks;
    }

    private List<String> semanticSplit(String text, int maxTokens, int overlapTokens) {
        List<String> chunks = new ArrayList<>();

        String[] paragraphs = text.split("\n\n");

        StringBuilder currentChunk = new StringBuilder();
        int currentTokenCount = 0;

        for (String paragraph : paragraphs) {
            int paragraphTokens = estimateTokenCount(paragraph);

            if (currentTokenCount + paragraphTokens > maxTokens && currentChunk.length() > 0) {
                chunks.add(currentChunk.toString().trim());

                String overlapText = getOverlapText(currentChunk.toString(), overlapTokens);
                currentChunk = new StringBuilder(overlapText);
                currentTokenCount = estimateTokenCount(overlapText);
            }

            currentChunk.append(paragraph).append("\n\n");
            currentTokenCount += paragraphTokens;
        }

        if (currentChunk.length() > 0) {
            chunks.add(currentChunk.toString().trim());
        }

        return chunks;
    }

    private String getOverlapText(String text, int overlapTokens) {
        String[] sentences = text.split("[。！？.!?]");

        if (sentences.length <= 1) {
            return "";
        }

        StringBuilder overlap = new StringBuilder();
        int tokenCount = 0;

        for (int i = sentences.length - 1; i >= 0 && tokenCount < overlapTokens; i--) {
            String sentence = sentences[i].trim();
            if (sentence.isEmpty()) continue;

            overlap.insert(0, sentence);
            tokenCount += estimateTokenCount(sentence);
        }

        return overlap.toString();
    }

    public List<RetrievedChunk> retrieve(String query, RetrieveOptions options) {
        int topK = options.getTopK() != null ? options.getTopK() : DEFAULT_TOP_K;
        float threshold = options.getSimilarityThreshold() != null ?
                options.getSimilarityThreshold() : DEFAULT_SIMILARITY_THRESHOLD;

        float[] queryVector = embeddingModel.embed(query);

        VectorSearchRequest request = new VectorSearchRequest();
        request.setCollection(options.getCollection() != null ? options.getCollection() : "knowledge");
        request.setQueryVector(queryVector);
        request.setLimit(topK * 2);

        List<VectorSearchResult> raw = vectorStore.search(request);
        List<RetrievedChunk> chunks = convertSearchResults(raw);

        if (rerankEnabled && chunks.size() > topK) {
            chunks = rerank(query, chunks);
        }

        return chunks.stream()
                .filter(c -> c.getSimilarity() >= threshold)
                .limit(topK)
                .collect(Collectors.toList());
    }

    public List<RetrievedChunk> retrieve(String query) {
        return retrieve(query, new RetrieveOptions());
    }

    private List<RetrievedChunk> rerank(String query, List<RetrievedChunk> chunks) {
        Map<String, Float> scores = new HashMap<>();

        for (RetrievedChunk chunk : chunks) {
            float score = calculateRelevanceScore(query, chunk.getContent());
            scores.put(chunk.getId(), score);
        }

        return chunks.stream()
                .sorted((a, b) -> Float.compare(
                        scores.getOrDefault(b.getId(), 0f),
                        scores.getOrDefault(a.getId(), 0f)))
                .collect(Collectors.toList());
    }

    private float calculateRelevanceScore(String query, String content) {
        float score = 0f;

        String[] queryTerms = query.toLowerCase().split("\\s+");
        String contentLower = content.toLowerCase();

        int matchCount = 0;
        for (String term : queryTerms) {
            if (contentLower.contains(term)) {
                matchCount++;
                score += 0.2f;
            }
        }

        score += (float) matchCount / queryTerms.length * 0.5f;

        return Math.min(1f, score);
    }

    private List<RetrievedChunk> convertSearchResults(List<VectorSearchResult> results) {
        List<RetrievedChunk> out = new ArrayList<>();
        for (VectorSearchResult r : results) {
            if (r.getEntry() == null) {
                continue;
            }
            RetrievedChunk chunk = new RetrievedChunk();
            chunk.setId(r.getEntry().getId());
            chunk.setContent(r.getEntry().getChunk());
            chunk.setSimilarity((float) r.getScore());
            chunk.setMetadata(new HashMap<>());
            out.add(chunk);
        }
        return out;
    }

    private Map<String, Object> createMetadata(Document document, int position) {
        Map<String, Object> metadata = new HashMap<>();
        metadata.put("source", document.getName());
        metadata.put("position", position);
        metadata.put("type", document.getType());
        return metadata;
    }

    private int estimateTokenCount(String text) {
        return (int) Math.ceil(text.length() / 4.0);
    }

    @Data
    public static class Document {
        private String id;
        private String name;
        private String content;
        private String type;
        private Map<String, Object> metadata;
    }

    @Data
    public static class RetrieveOptions {
        private Integer topK;
        private Float similarityThreshold;
        private String collection;
        private Map<String, Object> filters;
    }

    @Data
    public static class RetrievedChunk {
        private String id;
        private String content;
        private float similarity;
        private Map<String, Object> metadata;
    }
}