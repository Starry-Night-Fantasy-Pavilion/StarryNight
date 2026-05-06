package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class ContentExpandRequestDTO {

    @NotNull(message = "Chapter outline ID is required")
    private Long chapterOutlineId;

    @Min(value = 1, message = "Expand ratio must be >= 1")
    @Max(value = 5, message = "Expand ratio must be <= 5")
    private Integer expandRatio;

    private String styleSample;

    private Boolean optimizeConnections;

    private Boolean postProcessEnabled;
}

