package com.starrynight.engine.version;

import lombok.Data;

@Data
public class Diff {
    private String nodeId;
    private String nodeType;
    private ChangeType changeType;
    private String contentBefore;
    private String contentAfter;
    private String hashBefore;
    private String hashAfter;

    public enum ChangeType {
        ADDED("added", "新增"),
        MODIFIED("modified", "修改"),
        DELETED("deleted", "删除");

        private final String code;
        private final String description;

        ChangeType(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }
}