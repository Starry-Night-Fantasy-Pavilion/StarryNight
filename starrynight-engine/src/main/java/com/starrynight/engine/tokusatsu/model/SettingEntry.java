package com.starrynight.engine.tokusatsu.model;

import lombok.Data;
import java.util.List;
import java.util.ArrayList;

@Data
public class SettingEntry {
    private String id;
    private String name;
    private String content;
    private String sourceWork;
    private CanonStatus canonStatus;
    private List<String> applicableWorldlines;
    private boolean isCrossWorldValid;
    private List<String> conflictWorldlines;
    private EntryType entryType;

    public enum CanonStatus {
        CANON, THEATER, SPINOFF, NOVEL, STAGE, UNOFFICIAL
    }

    public enum EntryType {
        CHARACTER, DEVICE, FORM, LOCATION, ORGANIZATION, ABILITY, PLOT_DEVICE
    }

    public boolean isApplicableTo(String worldlineId) {
        if (applicableWorldlines == null || applicableWorldlines.isEmpty()) {
            return true;
        }
        return applicableWorldlines.contains(worldlineId);
    }

    public boolean hasConflictWith(String worldlineId) {
        if (conflictWorldlines == null) {
            return false;
        }
        return conflictWorldlines.contains(worldlineId);
    }
}
