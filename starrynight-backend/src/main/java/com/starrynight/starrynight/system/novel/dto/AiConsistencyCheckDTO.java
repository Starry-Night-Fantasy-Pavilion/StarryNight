package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class AiConsistencyCheckDTO {

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    @NotBlank(message = "Generated text is required")
    private String generatedText;

    private String coreEvent;

    private String sceneLocation;

    private String atmosphere;

    private String emotionalTone;

    private String generationMode;

    private List<String> presentCharacterIds = new ArrayList<>();

    private List<String> relatedOutlineNodes = new ArrayList<>();
}

