package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

public class DialogueContextAnchoring {

    private final CharacterRelationshipManager relationshipManager;
    private final CharacterStatusMonitor statusMonitor;
    private final EncounterValidator encounterValidator;

    public DialogueContextAnchoring(CharacterRelationshipManager relationshipManager,
                                     CharacterStatusMonitor statusMonitor,
                                     EncounterValidator encounterValidator) {
        this.relationshipManager = relationshipManager;
        this.statusMonitor = statusMonitor;
        this.encounterValidator = encounterValidator;
    }

    public DialogueContext buildDialogueContext(String characterId, SituationType situationType) {
        DialogueContext context = new DialogueContext();

        CharacterRelationship relationship = relationshipManager.getPrimaryRelationship(characterId);
        context.setRelationship(relationship);

        CharacterStatusInfo status = statusMonitor.getStatus(characterId);
        context.setStatus(status);

        context.setSituationType(situationType);
        context.setEmotionalAtmosphere(determineEmotionalAtmosphere(relationship, status, situationType));

        return context;
    }

    private EmotionalAtmosphere determineEmotionalAtmosphere(CharacterRelationship relationship,
                                                           CharacterStatusInfo status,
                                                           SituationType situationType) {
        EmotionalAtmosphere atmosphere = new EmotionalAtmosphere();
        atmosphere.setIntensity(50);

        if (relationship != null) {
            CharacterRelationship.RelationshipType type = relationship.getRelationshipType();
            switch (type) {
                case FRIEND, ALLY, FAMILY, LOVER -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.FRIENDLY);
                case ENEMY, GRUDGE, RIVAL -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.HOSTILE);
                case MENTOR -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.WARM);
                default -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.NEUTRAL);
            }
        }

        switch (situationType) {
            case BATTLE, CONFLICT -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.HOSTILE);
            case EMOTIONAL_SUPPORT, COOPERATION -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.WARM);
            case FIRST_MEETING -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.TENSE);
            default -> atmosphere.setOverall(EmotionalAtmosphere.AtmosphereType.NEUTRAL);
        }

        return atmosphere;
    }

    public DialogueStyleConfig getDialogueStyle(String characterId) {
        DialogueStyleConfig config = new DialogueStyleConfig();
        config.setCharacterId(characterId);

        DialogueStyleConfig.LanguageFingerprint fingerprint = new DialogueStyleConfig.LanguageFingerprint();
        fingerprint.setVocabularyLevel(DialogueStyleConfig.VocabularyLevel.CASUAL);
        fingerprint.setSpeechPatterns(new ArrayList<>());
        fingerprint.setCatchphrases(new ArrayList<>());
        config.setLanguageFingerprint(fingerprint);

        return config;
    }

    @Data
    public static class DialogueContext {
        private String characterId;
        private CharacterRelationship relationship;
        private CharacterStatusInfo status;
        private List<UniverseRule> universeRules;
        private SituationType situationType;
        private EmotionalAtmosphere emotionalAtmosphere;
    }

    public enum SituationType {
        FIRST_MEETING, REUNION, BATTLE, COOPERATION, EMOTIONAL_SUPPORT, CONFLICT, CONVERSATION, FAREWELL
    }

    @Data
    public static class EmotionalAtmosphere {
        private AtmosphereType overall;
        private int intensity;
        private List<String> dominantEmotions;

        public enum AtmosphereType {
            FRIENDLY, NEUTRAL, HOSTILE, TENSE, WARM
        }
    }

    @Data
    public static class DialogueStyleConfig {
        private String characterId;
        private LanguageFingerprint languageFingerprint;

        @Data
        public static class LanguageFingerprint {
            private VocabularyLevel vocabularyLevel;
            private List<String> speechPatterns;
            private List<String> catchphrases;
        }

        public enum VocabularyLevel {
            FORMAL, CASUAL, SLANG
        }
    }

    public interface CharacterRelationshipManager {
        CharacterRelationship getPrimaryRelationship(String characterId);
    }

    public interface CharacterStatusMonitor {
        CharacterStatusInfo getStatus(String characterId);
    }
}
