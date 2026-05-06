package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class FormEvolutionTimeline {
    private String characterId;
    private String characterName;
    private List<EvolutionEntry> evolutionPath;

    @Data
    public static class EvolutionEntry {
        private String formId;
        private String formName;
        private int unlockChapterNo;
        private String unlockCondition;
        private boolean isJumpUnlocked;
        private List<String> issues;
    }

    public static FormEvolutionTimeline create(String characterId, String characterName) {
        FormEvolutionTimeline timeline = new FormEvolutionTimeline();
        timeline.setCharacterId(characterId);
        timeline.setCharacterName(characterName);
        timeline.setEvolutionPath(new ArrayList<>());
        return timeline;
    }

    public void addEvolution(String formId, String formName, int chapterNo, String condition, boolean isJump) {
        EvolutionEntry entry = new EvolutionEntry();
        entry.setFormId(formId);
        entry.setFormName(formName);
        entry.setUnlockChapterNo(chapterNo);
        entry.setUnlockCondition(condition);
        entry.setJumpUnlocked(isJump);
        entry.setIssues(new ArrayList<>());
        this.evolutionPath.add(entry);
    }

    public void addIssue(int index, String issue) {
        if (index >= 0 && index < evolutionPath.size()) {
            this.evolutionPath.get(index).getIssues().add(issue);
        }
    }

    public int getUnresolvedJumps() {
        int count = 0;
        for (EvolutionEntry entry : evolutionPath) {
            if (entry.isJumpUnlocked() && (entry.getIssues() == null || entry.getIssues().isEmpty())) {
                count++;
            }
        }
        return count;
    }

    public String generateTimelineReport() {
        StringBuilder sb = new StringBuilder();
        sb.append("【形态进化时间轴 - ").append(characterName).append("】\n\n");

        for (int i = 0; i < evolutionPath.size(); i++) {
            EvolutionEntry entry = evolutionPath.get(i);
            sb.append(i + 1).append(". 第").append(entry.getUnlockChapterNo()).append("话 - ");
            sb.append(entry.getFormName());

            if (entry.isJumpUnlocked()) {
                sb.append(" [跳跃觉醒]");
            }
            sb.append("\n");

            sb.append("   条件: ").append(entry.getUnlockCondition()).append("\n");

            if (entry.getIssues() != null && !entry.getIssues().isEmpty()) {
                sb.append("   问题: ");
                for (String issue : entry.getIssues()) {
                    sb.append(issue).append("; ");
                }
                sb.append("\n");
            }
        }

        sb.append("\n未解释的跳跃: ").append(getUnresolvedJumps()).append("处\n");

        return sb.toString();
    }
}
