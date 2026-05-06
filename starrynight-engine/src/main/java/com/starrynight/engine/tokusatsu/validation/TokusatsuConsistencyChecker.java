package com.starrynight.engine.tokusatsu.validation;

import com.starrynight.engine.consistency.ConsistencyIssue;
import com.starrynight.engine.consistency.ConsistencyReport;
import com.starrynight.engine.tokusatsu.TokusatsuCharacterManager;
import com.starrynight.engine.tokusatsu.model.TokusatsuCharacter;
import com.starrynight.engine.tokusatsu.model.VillainTemplate;
import lombok.Data;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class TokusatsuConsistencyChecker {

    private final TokusatsuCharacterManager characterManager;
    private final TransformationValidator validator;

    public TokusatsuConsistencyChecker(TokusatsuCharacterManager characterManager, TransformationValidator validator) {
        this.characterManager = characterManager;
        this.validator = validator;
    }

    public ConsistencyReport check(Object content) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        if (content instanceof GeneratedChapter) {
            GeneratedChapter chapter = (GeneratedChapter) content;
            issues.addAll(checkTransformationCompliance(chapter));
            issues.addAll(checkMentalBurstReasonableness(chapter));
            issues.addAll(checkBattleLogic(chapter));
            issues.addAll(checkMissingAnnouncements(chapter));
            issues.addAll(checkVillainStatus(chapter));
        }

        return buildReport(issues);
    }

    private List<ConsistencyIssue> checkTransformationCompliance(GeneratedChapter chapter) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (TransformationEvent trans : chapter.getTransformations()) {
            TokusatsuCharacter character = characterManager.getCharacter(trans.getCharacterId());
            if (character == null) {
                continue;
            }

            TransformationValidator.ValidationResult validation = validator.validate(
                    character,
                    trans.getTargetFormId(),
                    TransformationValidator.SceneContext.create(trans.getEnergy(), trans.getEmotion())
            );

            if (!validation.isValid()) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("transformation");
                issue.setSeverity(mapSeverity(validation.getType()));
                issue.setLocation(trans.getLocation());
                issue.setDescription(validation.getError());
                issue.setSuggestion(validation.getSuggestion());
                issue.setAutoFixAvailable(validation.getType() == TransformationValidator.ValidationType.EMOTIONAL_MISMATCH);
                issues.add(issue);
            }
        }

        return issues;
    }

    private List<ConsistencyIssue> checkMentalBurstReasonableness(GeneratedChapter chapter) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (TransformationEvent trans : chapter.getTransformations()) {
            if (trans.isMentalBurst() && !isValidMentalBurstTrigger(trans)) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("mental_burst_potential");
                issue.setSeverity("warning");
                issue.setLocation(trans.getLocation());
                issue.setDescription("唯心爆发缺少合理的情绪触发条件");
                issue.setSuggestion("建议添加'同伴羁绊'、'不再迷茫'或'愤怒突破'等情绪铺垫");
                issue.setAutoFixAvailable(true);
                issue.setAutoFixAction("add_emotional_setup");
                issues.add(issue);
            }
        }

        return issues;
    }

    private boolean isValidMentalBurstTrigger(TransformationEvent trans) {
        String emotion = trans.getEmotion();
        if (emotion == null) return false;
        return emotion.contains("羁绊") ||
               emotion.contains("决心") ||
               emotion.contains("愤怒") ||
               emotion.contains("觉悟");
    }

    private List<ConsistencyIssue> checkBattleLogic(GeneratedChapter chapter) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (BattleEvent battle : chapter.getBattles()) {
            if (battle.getRounds() == null || battle.getRounds().isEmpty()) {
                continue;
            }

            int totalDamage = battle.getRounds().stream()
                    .mapToInt(BattleRound::getDamage)
                    .sum();

            if (totalDamage > 10000 && !battle.isUsedFinisher()) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("battle_logic");
                issue.setSeverity("info");
                issue.setLocation(battle.getLocation());
                issue.setDescription("战斗造成" + totalDamage + "点伤害但未使用必杀技");
                issue.setSuggestion("建议在关键战斗中使用必杀技提升观感");
                issues.add(issue);
            }
        }

        return issues;
    }

    private List<ConsistencyIssue> checkMissingAnnouncements(GeneratedChapter chapter) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (TransformationEvent trans : chapter.getTransformations()) {
            if (trans.getAnnouncement() == null || trans.getAnnouncement().isBlank()) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("missing_announcement");
                issue.setSeverity("info");
                issue.setLocation(trans.getLocation());
                issue.setDescription("变身缺少台词/音效");
                issue.setSuggestion("建议添加'HenShin!'等变身音效或台词");
                issue.setAutoFixAvailable(true);
                issue.setAutoFixAction("add_default_announcement");
                issues.add(issue);
            }
        }

        return issues;
    }

    private List<ConsistencyIssue> checkVillainStatus(GeneratedChapter chapter) {
        List<ConsistencyIssue> issues = new ArrayList<>();

        for (VillainStatusChange change : chapter.getVillainStatusChanges()) {
            VillainTemplate villain = change.getVillain();
            if (villain != null && !villain.isAlive() && change.getNewStatus() == VillainTemplate.VillainStatus.ALIVE) {
                ConsistencyIssue issue = new ConsistencyIssue();
                issue.setType("villain_status_conflict");
                issue.setSeverity("high");
                issue.setLocation(change.getLocation());
                issue.setDescription(villain.getName() + "已死亡，不能直接复活");
                issue.setSuggestion("需要给出合理解释：时光倒流/克隆/平行世界版本等");
                issues.add(issue);
            }
        }

        return issues;
    }

    private String mapSeverity(TransformationValidator.ValidationType type) {
        if (type == TransformationValidator.ValidationType.MISSING_DEVICE) {
            return "high";
        } else if (type == TransformationValidator.ValidationType.INSUFFICIENT_ENERGY) {
            return "high";
        } else {
            return "warning";
        }
    }

    private ConsistencyReport buildReport(List<ConsistencyIssue> issues) {
        ConsistencyReport report = new ConsistencyReport();
        report.setTotalIssues(issues.size());

        Map<String, List<ConsistencyIssue>> bySeverity = new HashMap<>();
        Map<String, List<ConsistencyIssue>> byType = new HashMap<>();

        for (ConsistencyIssue issue : issues) {
            bySeverity.computeIfAbsent(issue.getSeverity(), k -> new ArrayList<>()).add(issue);
            byType.computeIfAbsent(issue.getType(), k -> new ArrayList<>()).add(issue);
        }

        report.setIssuesBySeverity(bySeverity);
        report.setIssuesByType(byType);

        List<String> suggestions = new ArrayList<>();
        for (ConsistencyIssue issue : issues) {
            if (issue.getSuggestion() != null) {
                suggestions.add(issue.getSuggestion());
            }
        }
        report.setSuggestions(suggestions);

        return report;
    }

    @Data
    public static class GeneratedChapter {
        private int chapterNo;
        private String content;
        private List<TransformationEvent> transformations = new ArrayList<>();
        private List<BattleEvent> battles = new ArrayList<>();
        private List<VillainStatusChange> villainStatusChanges = new ArrayList<>();
    }

    @Data
    public static class TransformationEvent {
        private String characterId;
        private String targetFormId;
        private String location;
        private int energy;
        private String emotion;
        private String announcement;
        private boolean mentalBurst;
    }

    @Data
    public static class BattleEvent {
        private String enemyId;
        private String location;
        private List<BattleRound> rounds = new ArrayList<>();
        private boolean usedFinisher;
    }

    @Data
    public static class BattleRound {
        private int damage;
        private String description;
    }

    @Data
    public static class VillainStatusChange {
        private VillainTemplate villain;
        private VillainTemplate.VillainStatus newStatus;
        private String location;
    }
}
