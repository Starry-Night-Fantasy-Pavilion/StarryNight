package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class ContentExpandResultDTO {

    private String content;

    private Integer wordCount;

    private StyleFingerprint styleFingerprint;

    private List<Segment> segments = new ArrayList<>();

    private List<GenerationPlanItem> generationPlan = new ArrayList<>();

    @Data
    public static class Segment {
        private String type;
        private String text;
    }

    @Data
    public static class StyleFingerprint {
        private Double avgSentenceLength;
        private Double dialogueRatio;
        private Double descriptionDensity;
        private String pacingType;
    }

    @Data
    public static class GenerationPlanItem {
        private String type;
        private Integer sentenceCount;
        private String strategy;
    }
}

