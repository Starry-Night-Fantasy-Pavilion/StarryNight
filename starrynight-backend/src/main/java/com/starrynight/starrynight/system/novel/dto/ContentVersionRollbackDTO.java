package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class ContentVersionRollbackDTO {
    @NotNull(message = "Version ID is required")
    private Long versionId;
}

