package com.starrynight.engine.fanwork;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;

@Data
public class UniverseRule {
    private String id;
    private String universeId;
    private RuleType ruleType;
    private String content;
    private RuleScope scope;
    private List<String> conflictRules;
    private int priority;

    public enum RuleType {
        ENCOUNTER_RULE,
        ABILITY_COMPATIBILITY,
        TIMELINE_RULE,
        WORLD_RULE,
        CROSSOVER_RULE
    }

    @Data
    public static class RuleScope {
        private List<String> characters;
        private List<String> abilities;
        private List<String> locations;
        private String timeline;
    }

    public static UniverseRule createEncounterRule(String universeId, String content) {
        UniverseRule rule = new UniverseRule();
        rule.setId("encounter_" + System.currentTimeMillis());
        rule.setUniverseId(universeId);
        rule.setRuleType(RuleType.ENCOUNTER_RULE);
        rule.setContent(content);
        rule.setPriority(1);
        return rule;
    }

    public static UniverseRule createAbilityRule(String universeId, String content) {
        UniverseRule rule = new UniverseRule();
        rule.setId("ability_" + System.currentTimeMillis());
        rule.setUniverseId(universeId);
        rule.setRuleType(RuleType.ABILITY_COMPATIBILITY);
        rule.setContent(content);
        rule.setPriority(2);
        return rule;
    }
}
