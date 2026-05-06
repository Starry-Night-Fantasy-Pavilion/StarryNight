package com.starrynight.starrynight.system.novel.dto;

import com.starrynight.engine.consistency.ConsistencyReport;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class ChapterDraftDTO {

    private Integer chapterNo;

    private String title;

    private String coreEvent;

    private SceneSetting sceneSetting = new SceneSetting();

    private List<CharacterPresent> charactersPresent = new ArrayList<>();

    private List<PlotPoint> plotPoints = new ArrayList<>();

    private List<KeyDialogue> keyDialogues = new ArrayList<>();

    private List<Foreshadowing> foreshadowing = new ArrayList<>();

    private String connectionNote;

    private String status;

    private Integer version;

    private ConsistencyReport consistencyReport;

    @Data
    public static class SceneSetting {
        private String location;
        private String time;
        private String atmosphere;
    }

    @Data
    public static class CharacterPresent {
        private String name;
        private String chapterGoal;
        private String status;
    }

    @Data
    public static class PlotPoint {
        private Integer order;
        private String type;
        private String description;
        private String emotionalChange;
    }

    @Data
    public static class KeyDialogue {
        private String speaker;
        private String content;
        private String purpose;
    }

    @Data
    public static class Foreshadowing {
        private String setup;
        private String type;
    }
}

