package com.starrynight.starrynight.system.ai.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class AiSensitiveWordDTO {

    private Long id;

    @NotBlank(message = "Word is required")
    private String word;

    @NotNull(message = "Level is required")
    private Integer level;

    @NotNull(message = "Enabled is required")
    private Integer enabled;
}
