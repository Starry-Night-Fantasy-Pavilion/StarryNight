package com.starrynight.engine.version;

import lombok.Data;

@Data
public class Conflict {
    private String nodeId;
    private String nodeType;
    private String sourceValue;
    private String targetValue;
    private Resolution resolution;
    private String resolvedValue;

    public enum Resolution {
        USE_SOURCE("use_source", "使用源分支"),
        USE_TARGET("use_target", "使用目标分支"),
        MANUAL("manual", "手动解决");

        private final String code;
        private final String description;

        Resolution(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }
}