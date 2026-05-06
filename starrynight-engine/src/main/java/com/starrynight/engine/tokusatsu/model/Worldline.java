package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

@Data
public class Worldline {
    private String id;
    private String name;
    private String source;
    private CrossWorldRules crossWorldRules;
    private FusionRules fusionRules;
    private WorldlineStatus status;

    @Data
    public static class CrossWorldRules {
        private boolean canImportCharacters;
        private boolean canImportItems;
        private boolean conflictDetection;
    }

    @Data
    public static class FusionRules {
        private List<String> allowedWorldlines;
        private ConflictResolution conflictResolution;
    }

    public enum ConflictResolution {
        FIRST, MERGE, REJECT
    }

    public enum WorldlineStatus {
        ACTIVE, ARCHIVED, IF_BRANCH
    }

    public static Worldline createDefault(String id, String name, String source) {
        Worldline worldline = new Worldline();
        worldline.setId(id);
        worldline.setName(name);
        worldline.setSource(source);
        worldline.setStatus(WorldlineStatus.ACTIVE);

        CrossWorldRules crossWorldRules = new CrossWorldRules();
        crossWorldRules.setCanImportCharacters(true);
        crossWorldRules.setCanImportItems(true);
        crossWorldRules.setConflictDetection(true);
        worldline.setCrossWorldRules(crossWorldRules);

        return worldline;
    }

    public static Worldline createFusionWorldline(String id, String name, List<String> allowedWorldlines) {
        Worldline worldline = new Worldline();
        worldline.setId(id);
        worldline.setName(name);
        worldline.setSource("IF融合世界线");
        worldline.setStatus(WorldlineStatus.IF_BRANCH);

        CrossWorldRules crossWorldRules = new CrossWorldRules();
        crossWorldRules.setCanImportCharacters(true);
        crossWorldRules.setCanImportItems(true);
        crossWorldRules.setConflictDetection(true);
        worldline.setCrossWorldRules(crossWorldRules);

        FusionRules fusionRules = new FusionRules();
        fusionRules.setAllowedWorldlines(allowedWorldlines);
        fusionRules.setConflictResolution(ConflictResolution.MERGE);
        worldline.setFusionRules(fusionRules);

        return worldline;
    }
}
