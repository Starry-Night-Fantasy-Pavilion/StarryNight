package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class CanonPlotAnchor {
    private String id;
    private String chapterRange;
    private String title;
    private AlignmentType alignmentType;
    private CriticalContent criticalContent;
    private AllowedDeviation allowedDeviation;
    private ButterflyEffectMark butterflyEffect;

    public enum AlignmentType {
        CRITICAL,
        FLEXIBLE,
        FREE
    }

    @Data
    public static class CriticalContent {
        private List<CharacterState> characterStates;
        private List<PlotEvent> plotEvents;
        private List<RelationshipChange> relationshipChanges;
    }

    @Data
    public static class CharacterState {
        private String characterId;
        private String lifeStatus;
        private String emotionalState;
        private String location;
    }

    @Data
    public static class PlotEvent {
        private String eventId;
        private String description;
        private int chapterNo;
    }

    @Data
    public static class RelationshipChange {
        private String characterA;
        private String characterB;
        private String changeType;
        private int chapterNo;
    }

    @Data
    public static class AllowedDeviation {
        private boolean isAllowed;
        private DeviationLevel maxDeviationLevel;
    }

    public enum DeviationLevel {
        MINOR, MODERATE, MAJOR, ANY
    }

    @Data
    public static class ButterflyEffectMark {
        private boolean isSensitive;
        private int impactRadius;
        private String warningMessage;
    }

    public static CanonPlotAnchor create(String id, String range, String title, AlignmentType type) {
        CanonPlotAnchor anchor = new CanonPlotAnchor();
        anchor.setId(id);
        anchor.setChapterRange(range);
        anchor.setTitle(title);
        anchor.setAlignmentType(type);
        anchor.setCriticalContent(new CriticalContent());
        anchor.setAllowedDeviation(new AllowedDeviation());
        anchor.setButterflyEffect(new ButterflyEffectMark());
        return anchor;
    }
}
