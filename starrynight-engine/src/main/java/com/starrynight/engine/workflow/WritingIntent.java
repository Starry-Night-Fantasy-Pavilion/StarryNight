package com.starrynight.engine.workflow;

import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class WritingIntent {
    /**
     * 作品 ID；非空时检索仅召回该作品向量（metadata novelId 隔离）。
     */
    private Long novelId;

    private String coreEvent;
    private CurrentState currentState = new CurrentState();
    private List<PresentCharacter> presentCharacters = new ArrayList<>();
    private String emotionalTone;
    private List<String> relatedOutlineNodes = new ArrayList<>();
    private String generationMode;

    @Data
    public static class CurrentState {
        private String sceneLocation;
        private String narrativeTime;
        private String atmosphere;
        private String weather;
    }

    @Data
    public static class PresentCharacter {
        private String characterId;
        private String role;
        private String currentGoal;
    }
}

