package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class CharacterRelationship {
    private String id;
    private String characterA;
    private String characterB;
    private RelationshipType relationshipType;
    private RelationshipMetrics metrics;
    private List<Interaction> interactions;
    private List<KeyRelationshipEvent> keyEvents;
    private long createdAt;
    private long updatedAt;

    public enum RelationshipType {
        FRIEND,
        ALLY,
        LOVER,
        FAMILY,
        MENTOR,
        RIVAL,
        GRUDGE,
        ACQUAINTANCE,
        STRANGER,
        ENEMY
    }

    @Data
    public static class RelationshipMetrics {
        private int intimacy;
        private int trust;
        private double interactionFrequency;
        private int lastInteractionChapter;
        private int totalInteractions;

        public static RelationshipMetrics createDefault() {
            RelationshipMetrics metrics = new RelationshipMetrics();
            metrics.setIntimacy(50);
            metrics.setTrust(50);
            metrics.setInteractionFrequency(0);
            metrics.setLastInteractionChapter(0);
            metrics.setTotalInteractions(0);
            return metrics;
        }
    }

    @Data
    public static class Interaction {
        private String id;
        private int chapterNo;
        private String chapterTitle;
        private InteractionType interactionType;
        private String description;
        private int intimacyChange;
        private int trustChange;
        private List<String> emotionTags;
    }

    public enum InteractionType {
        FIRST_MEETING, BATTLE, CONVERSATION, COOPERATION, CONFLICT, EMOTIONAL_SUPPORT
    }

    @Data
    public static class KeyRelationshipEvent {
        private String id;
        private int chapterNo;
        private KeyEventType eventType;
        private String description;
        private ImpactType impactOnRelationship;
        private ImpactMetrics affectedMetrics;
    }

    public enum KeyEventType {
        FIRST_MEET, OATH, BETRAYAL, RECONCILIATION, DEATH, RESCUE
    }

    public enum ImpactType {
        POSITIVE, NEGATIVE, NEUTRAL
    }

    @Data
    public static class ImpactMetrics {
        private Integer intimacyChange;
        private Integer trustChange;
    }

    public static CharacterRelationship create(String charA, String charB, RelationshipType type) {
        CharacterRelationship relationship = new CharacterRelationship();
        relationship.setId(charA + "_" + charB);
        relationship.setCharacterA(charA);
        relationship.setCharacterB(charB);
        relationship.setRelationshipType(type);
        relationship.setMetrics(RelationshipMetrics.createDefault());
        relationship.setInteractions(new ArrayList<>());
        relationship.setKeyEvents(new ArrayList<>());
        relationship.setCreatedAt(System.currentTimeMillis());
        relationship.setUpdatedAt(System.currentTimeMillis());
        return relationship;
    }

    public void addInteraction(Interaction interaction) {
        this.interactions.add(interaction);
        this.metrics.setTotalInteractions(this.metrics.getTotalInteractions() + 1);
        this.metrics.setLastInteractionChapter(interaction.getChapterNo());
        this.metrics.setIntimacy(Math.max(0, Math.min(100, this.metrics.getIntimacy() + interaction.getIntimacyChange())));
        this.metrics.setTrust(Math.max(0, Math.min(100, this.metrics.getTrust() + interaction.getTrustChange())));
        this.metrics.setInteractionFrequency(this.metrics.getTotalInteractions() / Math.max(1, interaction.getChapterNo()));
        this.updatedAt = System.currentTimeMillis();
    }

    public void addKeyEvent(KeyRelationshipEvent event) {
        this.keyEvents.add(event);
        if (event.getAffectedMetrics() != null) {
            if (event.getAffectedMetrics().getIntimacyChange() != null) {
                this.metrics.setIntimacy(Math.max(0, Math.min(100,
                        this.metrics.getIntimacy() + event.getAffectedMetrics().getIntimacyChange())));
            }
            if (event.getAffectedMetrics().getTrustChange() != null) {
                this.metrics.setTrust(Math.max(0, Math.min(100,
                        this.metrics.getTrust() + event.getAffectedMetrics().getTrustChange())));
            }
        }
        this.updatedAt = System.currentTimeMillis();
    }
}
