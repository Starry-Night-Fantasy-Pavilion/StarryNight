package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class ChapterWorkshopIntentDTO {

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    @NotBlank(message = "Core event is required")
    private String coreEvent;

    private String sceneLocation;

    private String atmosphere;

    private String emotionalTone;

    private String generationMode;

    private String rewriteStrength;

    private Integer candidateCount;

    private String sourceContent;

    private List<String> presentCharacterIds = new ArrayList<>();

    private List<String> relatedOutlineNodes = new ArrayList<>();
}

