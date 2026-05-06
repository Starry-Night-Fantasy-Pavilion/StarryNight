package com.starrynight.engine.version;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class VersionSnapshot {
    private String id;
    private String nodeType;
    private String nodeId;
    private String content;
    private String commitId;
    private LocalDateTime createdAt;
}