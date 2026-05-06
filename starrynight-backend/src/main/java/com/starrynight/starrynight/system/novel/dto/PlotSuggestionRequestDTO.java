package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class PlotSuggestionRequestDTO {

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    @NotBlank(message = "Current content is required")
    private String currentContent;

    private String coreEvent;

    private String sceneLocation;

    private String emotionalTone;
}

