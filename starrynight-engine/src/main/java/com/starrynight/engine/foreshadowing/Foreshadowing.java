package com.starrynight.engine.foreshadowing;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;

@Data
public class Foreshadowing {
    private String id;
    private Long novelId;
    private Integer chapterNo;
    private String setupContent;
    private Float setupLocation;
    private ForeshadowingType type;
    private ForeshadowingStatus status;
    private Integer expectedChapterNo;
    private Integer autoDetectedExpected;
    private Float confidence;
    private LocalDateTime detectedAt;
    private LocalDateTime confirmedAt;
    private Boolean userEdited;
    private PayoffInfo payoffInfo;
    private List<String> relatedKeywords;

    @Data
    public static class PayoffInfo {
        private LocalDateTime paidOffAt;
        private Integer paidOffChapterNo;
        private String payoffMethod;
        private String payoffContent;
    }

    public enum ForeshadowingType {
        ITEM("item", "物品伏笔"),
        IDENTITY("identity", "身份伏笔"),
        RELATIONSHIP("relationship", "关系伏笔"),
        ABILITY("ability", "能力伏笔"),
        PLOT("plot", "情节伏笔"),
        WORLD("world", "世界观伏笔"),
        DIALOGUE("dialogue", "对白伏笔");

        private final String code;
        private final String description;

        ForeshadowingType(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }

    public enum ForeshadowingStatus {
        PENDING("pending", "待确认"),
        CONFIRMED("confirmed", "已确认"),
        PAID_OFF("paid_off", "已回收"),
        EXPIRED("expired", "过期未回收"),
        CANCELLED("cancelled", "已取消");

        private final String code;
        private final String description;

        ForeshadowingStatus(String code, String description) {
            this.code = code;
            this.description = description;
        }

        public String getCode() { return code; }
        public String getDescription() { return description; }
    }
}