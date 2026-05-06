package com.starrynight.engine.version;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;

@Data
public class Branch {
    private String id;
    private String name;
    private String description;
    private String baseVersionId;
    private String rootCommitId;
    private String headCommitId;
    private String parentBranchId;
    private String novelId;
    private BranchStatus status;
    private LocalDateTime createdAt;
    private LocalDateTime mergedAt;

    public enum BranchStatus {
        ACTIVE("active", "活跃"),
        MERGED("merged", "已合并"),
        ARCHIVED("archived", "已归档");

        private final String code;
        private final String description;

        BranchStatus(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }
}