package com.starrynight.starrynight.system.knowledge.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class KnowledgeChunkDTO {

    private Long id;

    private Long libraryId;

    private String content;

    private String contentHash;

    private Integer chunkOrder;

    private Integer tokenCount;

    private String vectorId;

    private Object metadata;

    private LocalDateTime createTime;
}