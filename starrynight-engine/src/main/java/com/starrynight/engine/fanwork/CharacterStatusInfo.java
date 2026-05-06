package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class CharacterStatusInfo {
    private String characterId;
    private int chapterNo;
    private LifeStatus lifeStatus;
    private HealthStatus health;
    private EmotionalStatus emotional;
    private AbilityStatus ability;
    private LocationStatus location;
    private List<StatusChange> statusHistory;
    private long updatedAt;

    public enum LifeStatus {
        ALIVE, DEAD, UNKNOWN
    }

    @Data
    public static class HealthStatus {
        private int value;
        private List<Injury> injuries;
        private int fatigue;
        private boolean needsRecovery;

        public static HealthStatus createDefault() {
            HealthStatus status = new HealthStatus();
            status.setValue(100);
            status.setInjuries(new ArrayList<>());
            status.setFatigue(0);
            status.setNeedsRecovery(false);
            return status;
        }

        public void addInjury(Injury injury) {
            this.injuries.add(injury);
            if (injury.getSeverity() == Injury.Severity.MAJOR) {
                this.value -= 30;
            } else if (injury.getSeverity() == Injury.Severity.CRITICAL) {
                this.value -= 50;
            } else {
                this.value -= 10;
            }
            this.value = Math.max(0, this.value);
            if (this.value < 30) {
                this.needsRecovery = true;
            }
        }
    }

    @Data
    public static class Injury {
        private String id;
        private InjuryType type;
        private Severity severity;
        private String description;
        private Integer healingChapter;
        private boolean isPersistent;

        public enum InjuryType {
            PHYSICAL, MENTAL, SPIRITUAL
        }

        public enum Severity {
            MINOR, MAJOR, CRITICAL
        }
    }

    @Data
    public static class EmotionalStatus {
        private int value;
        private EmotionalType emotion;
        private int volatility;
        private MentalState mentalState;
        private List<PsychologicalEvent> psychologicalEvents;

        public static EmotionalStatus createDefault() {
            EmotionalStatus status = new EmotionalStatus();
            status.setValue(50);
            status.setEmotion(EmotionalType.CALM);
            status.setVolatility(20);
            status.setMentalState(MentalState.STABLE);
            status.setPsychologicalEvents(new ArrayList<>());
            return status;
        }
    }

    public enum EmotionalType {
        JOY, CALM, ANXIETY, ANGER, GRIEF, FEAR, DETERMINATION, CONFUSION
    }

    public enum MentalState {
        STABLE, UNSTABLE, BREAKING
    }

    @Data
    public static class PsychologicalEvent {
        private String id;
        private int chapterNo;
        private String type;
        private String description;
        private int emotionalImpact;
    }

    @Data
    public static class AbilityStatus {
        private int performance;
        private int energy;
        private List<AbilityLimitation> limitations;
        private SpecialState specialState;

        public static AbilityStatus createDefault() {
            AbilityStatus status = new AbilityStatus();
            status.setPerformance(100);
            status.setEnergy(100);
            status.setLimitations(new ArrayList<>());
            return status;
        }
    }

    @Data
    public static class AbilityLimitation {
        private LimitationType type;
        private String description;
        private boolean recoverable;
        private String recoveryCondition;

        public enum LimitationType {
            COOLDOWN, RESOURCE_DEPLETED, INJURY_PENALTY, SEALED
        }
    }

    public enum SpecialState {
        MENTAL_BURST, AWAKENING, BERSERK, SEALED
    }

    @Data
    public static class LocationStatus {
        private String currentLocation;
        private String currentWorldline;
        private int timelinePosition;
    }

    @Data
    public static class StatusChange {
        private int chapterNo;
        private String changeType;
        private String description;
        private int previousValue;
        private int newValue;
    }

    public static CharacterStatusInfo createDefault(String characterId) {
        CharacterStatusInfo status = new CharacterStatusInfo();
        status.setCharacterId(characterId);
        status.setChapterNo(0);
        status.setLifeStatus(LifeStatus.ALIVE);
        status.setHealth(HealthStatus.createDefault());
        status.setEmotional(EmotionalStatus.createDefault());
        status.setAbility(AbilityStatus.createDefault());
        status.setStatusHistory(new ArrayList<>());
        status.setUpdatedAt(System.currentTimeMillis());
        return status;
    }

    public void updateFromChapter(int chapterNo) {
        this.chapterNo = chapterNo;
        this.updatedAt = System.currentTimeMillis();
    }

    public void recordStatusChange(String type, String desc, int prev, int next) {
        StatusChange change = new StatusChange();
        change.setChapterNo(this.chapterNo);
        change.setChangeType(type);
        change.setDescription(desc);
        change.setPreviousValue(prev);
        change.setNewValue(next);
        this.statusHistory.add(change);
    }
}
