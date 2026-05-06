package com.starrynight.starrynight.system.ai.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class AiTemplateDTO {

    private Long id;

    @NotBlank(message = "Name is required")
    private String name;

    @NotBlank(message = "Type is required")
    private String type;

    private String description;

    @NotBlank(message = "Content is required")
    private String content;

    @NotNull(message = "Enabled is required")
    private Integer enabled;

    private Integer usageCount;
}
