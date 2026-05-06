package com.starrynight.engine.rhythm;

import lombok.Data;
import java.util.List;

@Data
public class ConflictDensity {
    private Integer chapterNo;
    private Integer conflictCount;
    private Float intensitySum;
    private Float densityPerThousandWords;
    private List<Conflict> conflicts;
    private String intensityLevel;

    @Data
    public static class Conflict {
        private ConflictType type;
        private Integer intensity;
        private Float position;
        private String description;

        public enum ConflictType {
            PHYSICAL("physical", "肢体冲突"),
            VERBAL("verbal", "语言冲突"),
            PSYCHOLOGICAL("psychological", "心理冲突"),
            SITUATIONAL("situational", "情景冲突");

            private final String code;
            private final String description;

            ConflictType(String code, String description) {
                this.code = code;
                this.description = description;
            }

            public String getCode() { return code; }
            public String getDescription() { return description; }
        }
    }

    public String getIntensityLevel() {
        if (densityPerThousandWords == null) return "unknown";
        if (densityPerThousandWords < 2) return "low";
        if (densityPerThousandWords < 5) return "medium";
        return "high";
    }
}