package com.starrynight.engine.rag;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

@Data
public class KnowledgeChunk {
    private String id;
    private String documentId;
    private String content;
    private Map<String, Object> metadata;
    private float[] vector;
    private Integer tokenCount;
    private LocalDateTime createdAt;

    public enum ChunkStatus {
        ACTIVE,
        ARCHIVED,
        MERGED
    }

    private ChunkStatus status;
}