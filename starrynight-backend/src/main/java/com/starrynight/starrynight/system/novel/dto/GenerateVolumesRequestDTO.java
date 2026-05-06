package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class GenerateVolumesRequestDTO {

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    @Min(value = 1, message = "volumeCount must be >= 1")
    @Max(value = 12, message = "volumeCount must be <= 12")
    private Integer volumeCount;
}

