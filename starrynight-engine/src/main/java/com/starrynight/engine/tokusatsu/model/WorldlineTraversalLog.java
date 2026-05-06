package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.ArrayList;
import java.util.List;

@Data
public class WorldlineTraversalLog {
    private String storyId;
    private List<TraversalRecord> traversals;
    private List<Inconsistency> inconsistencies;

    @Data
    public static class TraversalRecord {
        private String fromWorldline;
        private String toWorldline;
        private int chapterNo;
        private String reason;
        private List<String> charactersBrought;
        private List<String> itemsBrought;
        private List<String> itemsLost;
        private List<String> plotConsequences;
    }

    @Data
    public static class Inconsistency {
        private InconsistencyType type;
        private String description;
        private String location;
    }

    public enum InconsistencyType {
        ITEM_APPEARANCE, CHARACTER_STATE, RULE_CONFLICT
    }

    public static WorldlineTraversalLog create(String storyId) {
        WorldlineTraversalLog log = new WorldlineTraversalLog();
        log.setStoryId(storyId);
        log.setTraversals(new ArrayList<>());
        log.setInconsistencies(new ArrayList<>());
        return log;
    }

    public void addTraversal(String from, String to, int chapter, String reason,
                            List<String> characters, List<String> items,
                            List<String> lost, List<String> consequences) {
        TraversalRecord record = new TraversalRecord();
        record.setFromWorldline(from);
        record.setToWorldline(to);
        record.setChapterNo(chapter);
        record.setReason(reason);
        record.setCharactersBrought(characters != null ? characters : new ArrayList<>());
        record.setItemsBrought(items != null ? items : new ArrayList<>());
        record.setItemsLost(lost != null ? lost : new ArrayList<>());
        record.setPlotConsequences(consequences != null ? consequences : new ArrayList<>());
        this.traversals.add(record);
    }

    public void addInconsistency(InconsistencyType type, String description, String location) {
        Inconsistency issue = new Inconsistency();
        issue.setType(type);
        issue.setDescription(description);
        issue.setLocation(location);
        this.inconsistencies.add(issue);
    }

    public boolean hasInconsistencies() {
        return !this.inconsistencies.isEmpty();
    }

    public String generateLogReport() {
        StringBuilder sb = new StringBuilder();
        sb.append("【世界线穿越日志 - ").append(storyId).append("】\n\n");

        if (traversals.isEmpty()) {
            sb.append("暂无世界线穿越记录。\n");
            return sb.toString();
        }

        for (int i = 0; i < traversals.size(); i++) {
            TraversalRecord record = traversals.get(i);
            sb.append("【穿越").append(i + 1).append("】\n");
            sb.append("第").append(record.getChapterNo()).append("话: ");
            sb.append(record.getFromWorldline()).append(" → ").append(record.getToWorldline()).append("\n");
            sb.append("原因: ").append(record.getReason()).append("\n");

            if (!record.getCharactersBrought().isEmpty()) {
                sb.append("携带角色: ").append(String.join("、", record.getCharactersBrought())).append("\n");
            }
            if (!record.getItemsBrought().isEmpty()) {
                sb.append("携带道具: ").append(String.join("、", record.getItemsBrought())).append("\n");
            }
            if (!record.getItemsLost().isEmpty()) {
                sb.append("丢失道具: ").append(String.join("、", record.getItemsLost())).append("\n");
            }
            if (!record.getPlotConsequences().isEmpty()) {
                sb.append("剧情影响: ").append(String.join("、", record.getPlotConsequences())).append("\n");
            }
            sb.append("\n");
        }

        if (!inconsistencies.isEmpty()) {
            sb.append("【一致性冲突】\n");
            for (Inconsistency issue : inconsistencies) {
                sb.append("- ").append(issue.getType()).append(": ");
                sb.append(issue.getDescription());
                sb.append(" (").append(issue.getLocation()).append(")\n");
            }
        }

        return sb.toString();
    }
}
