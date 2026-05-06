package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class ButterflyEffectTracker {

    private final Map<String, TimelineBranch> branches;
    private final Map<String, ButterflyEffectEvent> events;
    private static final double CASCADE_FACTOR = 0.7;

    public ButterflyEffectTracker() {
        this.branches = new HashMap<>();
        this.events = new HashMap<>();
    }

    public List<EffectPropagation> recordTravelerAction(TravelerAction action) {
        List<EffectPropagation> effects = new ArrayList<>();

        List<EffectPropagation> directEffects = analyzeDirectEffects(action);
        effects.addAll(directEffects);

        for (EffectPropagation direct : directEffects) {
            List<EffectPropagation> indirectEffects = analyzeIndirectEffects(direct);
            effects.addAll(indirectEffects);
        }

        updateTimelineState(action, effects);
        List<Conflict> conflicts = detectConflicts(effects);

        return effects;
    }

    private List<EffectPropagation> analyzeDirectEffects(TravelerAction action) {
        List<EffectPropagation> effects = new ArrayList<>();
        List<AffectedEntity> affectedEntities = findAffectedEntities(action);

        for (AffectedEntity entity : affectedEntities) {
            EffectPropagation effect = new EffectPropagation();
            effect.setId(generateId());
            effect.setTriggerAction(action.getId());
            effect.setAffectedEntity(entity);
            effect.setEffectType(EffectType.DIRECT);
            effect.setChapterNo(action.getChapterNo());
            effect.setDeviationLevel(calculateDeviation(entity, action));
            effects.add(effect);
        }

        return effects;
    }

    private List<EffectPropagation> analyzeIndirectEffects(EffectPropagation directEffect) {
        List<EffectPropagation> cascadeEffects = new ArrayList<>();
        List<AffectedEntity> secondOrderAffected = findSecondOrderAffected(directEffect.getAffectedEntity());

        for (AffectedEntity entity : secondOrderAffected) {
            String contradiction = checkContradiction(entity, directEffect);
            if (contradiction != null) {
                EffectPropagation cascade = new EffectPropagation();
                cascade.setId(generateId());
                cascade.setTriggerAction(directEffect.getTriggerAction());
                cascade.setAffectedEntity(entity);
                cascade.setEffectType(EffectType.CASCADE);
                cascade.setChapterNo(estimateChapter(entity));
                cascade.setDeviationLevel(directEffect.getDeviationLevel() * CASCADE_FACTOR);
                cascade.setHasContradiction(true);
                cascade.setContradictionDetails(contradiction);
                cascadeEffects.add(cascade);
            }
        }

        return cascadeEffects;
    }

    private List<AffectedEntity> findAffectedEntities(TravelerAction action) {
        return new ArrayList<>();
    }

    private List<AffectedEntity> findSecondOrderAffected(AffectedEntity entity) {
        return new ArrayList<>();
    }

    private double calculateDeviation(AffectedEntity entity, TravelerAction action) {
        return 10.0;
    }

    private String checkContradiction(AffectedEntity entity, EffectPropagation cause) {
        return null;
    }

    private int estimateChapter(AffectedEntity entity) {
        return 1;
    }

    private void updateTimelineState(TravelerAction action, List<EffectPropagation> effects) {
    }

    private List<Conflict> detectConflicts(List<EffectPropagation> effects) {
        List<Conflict> conflicts = new ArrayList<>();
        return conflicts;
    }

    private String generateId() {
        return "effect_" + System.currentTimeMillis();
    }

    public void createBranch(TimelineBranch branch) {
        this.branches.put(branch.getId(), branch);
    }

    public TimelineBranch getBranch(String branchId) {
        return this.branches.get(branchId);
    }

    @Data
    public static class TravelerAction {
        private String id;
        private int chapterNo;
        private String description;
        private String branchId;
        private ActionType type;
        private String sourceCharacterId;

        public enum ActionType {
            TRAVELER_ACTION, DIVINE_INTERVENTION, ORIGINAL_PLOT
        }
    }

    @Data
    public static class EffectPropagation {
        private String id;
        private String triggerAction;
        private AffectedEntity affectedEntity;
        private EffectType effectType;
        private int chapterNo;
        private double deviationLevel;
        private boolean hasContradiction;
        private String contradictionDetails;
    }

    public enum EffectType {
        DIRECT, INDIRECT, CASCADE
    }

    @Data
    public static class AffectedEntity {
        private String entityId;
        private String entityType;
        private String description;
    }

    @Data
    public static class TimelineBranch {
        private String id;
        private String name;
        private String parentBranchId;
        private ForkPoint forkPoint;
        private BranchStatus status;
        private ImpactScope impactScope;
        private int divergenceLevel;

        public enum BranchStatus {
            ACTIVE, MERGED, COLLAPSED
        }

        @Data
        public static class ForkPoint {
            private int chapterNo;
            private String originalEvent;
            private String travelerAction;
            private String divergenceDescription;
        }

        @Data
        public static class ImpactScope {
            private List<Integer> affectedChapters;
            private List<String> affectedCharacters;
            private List<String> affectedRelationships;
        }
    }

    @Data
    public static class ButterflyEffectEvent {
        private String id;
        private String branchId;
        private EventInfo event;
        private TriggerInfo trigger;
        private ImpactInfo impact;
        private TraceabilityInfo traceability;

        @Data
        public static class EventInfo {
            private int chapterNo;
            private String description;
            private EventCategory type;
        }

        public enum EventCategory {
            DIRECT, INDIRECT, CASCADE
        }

        @Data
        public static class TriggerInfo {
            private TriggerType type;
            private String sourceAction;
        }

        public enum TriggerType {
            TRAVELER_ACTION, DIVINE_INTERVENTION, ORIGINAL_PLOT
        }

        @Data
        public static class ImpactInfo {
            private List<CharacterImpact> characters;
            private List<RelationshipImpact> relationships;
            private double plotDeviation;
        }

        @Data
        public static class CharacterImpact {
            private String characterId;
            private String impactType;
        }

        @Data
        public static class RelationshipImpact {
            private String characterA;
            private String characterB;
            private String impactType;
        }

        @Data
        public static class TraceabilityInfo {
            private boolean isTraceable;
            private List<String> causeChain;
            private String originalCauseId;
        }
    }

    @Data
    public static class Conflict {
        private ConflictType type;
        private String description;
        private String location;
        private ConflictSeverity severity;

        public enum ConflictType {
            CRITICAL_NODE, CHARACTER_STATE, TIMELINE_PARADOX
        }

        public enum ConflictSeverity {
            HIGH, MEDIUM, LOW
        }
    }
}
