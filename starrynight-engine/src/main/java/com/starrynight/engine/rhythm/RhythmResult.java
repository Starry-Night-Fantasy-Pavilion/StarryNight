package com.starrynight.engine.rhythm;

import lombok.Data;
import java.util.List;

@Data
public class RhythmResult {
    private Long novelId;
    private List<ChapterRhythm> chapters;
    private List<RhythmSuggestion> suggestions;
    private Float overallScore;
    private RhythmSummary summary;

    @Data
    public static class ChapterRhythm {
        private Integer chapterNo;
        private EmotionCurve emotions;
        private ConflictDensity conflictDensity;
        private RetentionPrediction retentionPrediction;
    }

    @Data
    public static class RetentionPrediction {
        private Integer chapterNo;
        private Float retentionScore;
        private Float predictedChurnRate;
        private String rating;
        private List<String> suggestions;
    }

    @Data
    public static class RhythmSuggestion {
        private String type;
        private String priority;
        private Integer targetChapterNo;
        private String description;
        private String action;
        private String estimatedImpact;
    }

    @Data
    public static class RhythmSummary {
        private Float averageTension;
        private Float averageWarmth;
        private Float totalConflicts;
        private String pacingAssessment;
        private List<String> strengths;
        private List<String> weaknesses;
    }
}