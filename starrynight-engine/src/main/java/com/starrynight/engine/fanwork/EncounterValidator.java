package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class EncounterValidator {

    private final Map<String, UniverseRule> rules;
    private final Map<String, CrossoverCompatibility> compatibilityMatrix;

    public EncounterValidator() {
        this.rules = new HashMap<>();
        this.compatibilityMatrix = new HashMap<>();
    }

    public EncounterResult validateEncounter(EncounterContext context) {
        EncounterResult result = new EncounterResult();
        result.setAllowed(true);
        result.setRestrictions(new ArrayList<>());
        result.setWarnings(new ArrayList<>());

        List<UniverseRule> applicableRules = findApplicableRules(context);

        for (UniverseRule rule : applicableRules) {
            EncounterRestriction restriction = checkRule(rule, context);
            if (restriction != null) {
                if (restriction.getSeverity() == EncounterRestriction.Severity.ERROR) {
                    result.setAllowed(false);
                }
                result.getRestrictions().add(restriction);
            }
        }

        return result;
    }

    private List<UniverseRule> findApplicableRules(EncounterContext context) {
        List<UniverseRule> applicable = new ArrayList<>();
        for (UniverseRule rule : rules.values()) {
            if (rule.getRuleType() == UniverseRule.RuleType.ENCOUNTER_RULE) {
                applicable.add(rule);
            }
        }
        return applicable;
    }

    private EncounterRestriction checkRule(UniverseRule rule, EncounterContext context) {
        return null;
    }

    public void addRule(UniverseRule rule) {
        this.rules.put(rule.getId(), rule);
    }

    public void setCompatibility(String universeA, String universeB, CrossoverCompatibility.CompatibilityLevel level) {
        String key = universeA + "_" + universeB;
        CrossoverCompatibility compatibility = new CrossoverCompatibility();
        compatibility.setUniverseA(universeA);
        compatibility.setUniverseB(universeB);
        compatibility.setCompatibilityLevel(level);
        this.compatibilityMatrix.put(key, compatibility);
    }

    public CrossoverCompatibility getCompatibility(String universeA, String universeB) {
        String key = universeA + "_" + universeB;
        return compatibilityMatrix.get(key);
    }

    @Data
    public static class EncounterContext {
        private int chapterNo;
        private String timeline;
        private String location;
        private String currentWorldline;
        private List<String> charactersInvolved;
        private List<String> abilitiesUsed;
    }

    @Data
    public static class EncounterResult {
        private boolean allowed;
        private List<EncounterRestriction> restrictions;
        private List<String> warnings;
        private List<String> conflictRules;
    }

    @Data
    public static class EncounterRestriction {
        private RestrictionType type;
        private String description;
        private Severity severity;
        private String suggestion;

        public enum RestrictionType {
            TIMELINE_CONFLICT, ABILITY_CONFLICT, STATUS_CONFLICT, WORLDLINE_CONFLICT
        }

        public enum Severity {
            ERROR, WARNING, INFO
        }
    }

    @Data
    public static class CrossoverCompatibility {
        private String universeA;
        private String universeB;
        private CompatibilityLevel compatibilityLevel;
        private FusionRules fusionRules;
        private List<AbilityMapping> abilityMapping;

        public enum CompatibilityLevel {
            FULL, PARTIAL, NONE
        }
    }

    @Data
    public static class FusionRules {
        private boolean allowed;
        private List<String> conditions;
        private List<String> conflicts;
    }

    @Data
    public static class AbilityMapping {
        private String originalAbility;
        private String mappedAbility;
        private String mappingRule;
    }
}
