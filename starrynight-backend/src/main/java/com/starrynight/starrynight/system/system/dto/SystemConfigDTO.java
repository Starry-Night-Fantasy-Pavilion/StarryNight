package com.starrynight.starrynight.system.system.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class SystemConfigDTO {

    private Long id;

    @NotBlank(message = "Config key is required")
    private String configKey;

    private String configValue;

    private String configType;

    @NotBlank(message = "Config name is required")
    private String configName;

    private String configGroup;

    private String description;

    private Integer editable;
}

