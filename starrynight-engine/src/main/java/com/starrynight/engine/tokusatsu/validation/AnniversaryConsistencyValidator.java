package com.starrynight.engine.tokusatsu.validation;

import com.starrynight.engine.consistency.ConsistencyIssue;
import lombok.Data;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class AnniversaryConsistencyValidator {

    private final CharacterStatusManager statusManager;

    public AnniversaryConsistencyValidator(CharacterStatusManager statusManager) {
        this.statusManager = statusManager;
    }

    public ValidationResult validate(AssemblyScene scene) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (AssemblyEntity entity : scene.getEntities()) {
            CharacterFinalStatus finalStatus = statusManager.getCharacterFinalStatus(entity.getId());

            if (finalStatus.getStatus() == CharacterStatus.DECEASED && !entity.isHasExplanation()) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("character_state");
                issue.setSeverity("high");
                issue.setDescription(entity.getName() + "已退场，不能以原状态直接登场");
                issue.setSuggestion("需要给出合理解释: 时光倒流/克隆/平行世界版本等");
                issue.setLocation(scene.getLocation());
                issues.add(issue);
            }

            if (entity.getFormId() != null &&
                finalStatus.getLastForm() != null &&
                !entity.getFormId().equals(finalStatus.getLastForm())) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("form_mismatch");
                issue.setSeverity("medium");
                issue.setDescription(entity.getName() + "的形态与正史不符");
                issue.setSuggestion("当前应为" + finalStatus.getLastForm() + "而非" + entity.getFormId());
                issue.setLocation(scene.getLocation());
                issues.add(issue);
            }

            if (finalStatus.getStatus() == CharacterStatus.DECEASED && entity.isHasExplanation()) {
                if (entity.getExplanationType() == null) {
                    ConsistencyIssue issue = new ConsistencyIssue();
                    issue.setType("missing_explanation");
                    issue.setSeverity("warning");
                    issue.setDescription(entity.getName() + "标注有解释但未指定解释类型");
                    issue.setSuggestion("请明确标注: time_reverse/clone/parallel_world版本等");
                    issue.setLocation(scene.getLocation());
                    issues.add(issue);
                }
            }
        }

        ValidationResult result = new ValidationResult();
        result.setValid(issues.isEmpty());
        result.setIssues(issues);
        return result;
    }

    public interface CharacterStatusManager {
        CharacterFinalStatus getCharacterFinalStatus(String characterId);
    }

    @Data
    public static class AssemblyScene {
        private String location;
        private List<AssemblyEntity> entities = new ArrayList<>();
    }

    @Data
    public static class AssemblyEntity {
        private String id;
        private String name;
        private String formId;
        private boolean hasExplanation;
        private ExplanationType explanationType;
    }

    public enum ExplanationType {
        TIME_REVERSE, CLONE, PARALLEL_WORLD, REVIVAL, POSSESSION
    }

    @Data
    public static class CharacterFinalStatus {
        private String characterId;
        private CharacterStatus status;
        private String lastForm;
        private String lastAppearance;
    }

    public enum CharacterStatus {
        ACTIVE, DECEASED, UNKNOWN
    }

    @Data
    public static class ValidationResult {
        private boolean valid;
        private List<ConsistencyIssue> issues;
    }
}
