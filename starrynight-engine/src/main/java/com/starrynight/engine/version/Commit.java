package com.starrynight.engine.version;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;

@Data
public class Commit {
    private String id;
    private String branchId;
    private List<String> parentIds;
    private String nodeType;
    private String nodeId;
    private ChangeType changeType;
    private String contentBefore;
    private String contentAfter;
    private String message;
    private Author author;
    private String aiConversationId;
    private LocalDateTime createdAt;

    public enum ChangeType {
        CREATE("create", "创建"),
        UPDATE("update", "更新"),
        DELETE("delete", "删除");

        private final String code;
        private final String description;

        ChangeType(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }

    public enum Author {
        USER("user", "用户"),
        AI("ai", "AI");

        private final String code;
        private final String description;

        Author(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }
}