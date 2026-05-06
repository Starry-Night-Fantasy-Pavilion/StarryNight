package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class ContentVersionSaveDTO {

    @NotNull(message = "Chapter outline ID is required")
    private Long chapterOutlineId;

    @NotBlank(message = "Expanded content is required")
    private String content;
}

