package com.starrynight.engine.vector;

import lombok.Data;

import java.time.Instant;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Data
public class VectorMetadata {
    private EntryType type;
    private String subType;
    private EntityIds entityIds;
    private String narrativeTimestamp;
    private Instant createdAt;
    private Instant updatedAt;
    private EntryStatus status = EntryStatus.ACTIVE;
    private double importanceWeight = 0.5d;
    private List<String> tags = new ArrayList<>();
    private String foreshadowingId;
    /** 任意字符串过滤键（如跨世界伏笔 worldlineId），参与 metadataEquals 匹配 */
    private Map<String, String> extras = new HashMap<>();

    public boolean matchesMetadataEquals(Map<String, String> equals) {
        if (equals == null || equals.isEmpty()) {
            return true;
        }
        for (Map.Entry<String, String> kv : equals.entrySet()) {
            String actual = resolveString(kv.getKey());
            if (actual == null || !actual.equals(kv.getValue())) {
                return false;
            }
        }
        return true;
    }

    private String resolveString(String k) {
        if (extras != null && extras.containsKey(k)) {
            return extras.get(k);
        }
        if (entityIds == null) {
            return null;
        }
        return switch (k) {
            case "novelId" -> entityIds.getNovelId();
            case "characterId" -> entityIds.getCharacterId();
            case "locationId" -> entityIds.getLocationId();
            case "itemId" -> entityIds.getItemId();
            case "chapterId" -> entityIds.getChapterId();
            case "outlineNodeId" -> entityIds.getOutlineNodeId();
            default -> null;
        };
    }
}

