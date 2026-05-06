package com.starrynight.engine.foreshadowing;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;

@Data
public class PayoffCheckResult {
    private boolean paidOff;
    private String matchType;
    private Integer matchedChapter;
    private Float confidence;
    private List<String> suggestions;
    private String matchedContent;

    @Data
    public static class PayoffMatch {
        private Integer chapterNo;
        private String matchedContent;
        private Float similarityScore;
        private String matchType;
    }
}