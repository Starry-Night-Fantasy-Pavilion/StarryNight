package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class ChapterDraftGenerateDTO {

    @NotNull(message = "Volume ID is required")
    private Long volumeId;

    @NotNull(message = "Chapter count is required")
    @Min(value = 1, message = "Chapter count must be >= 1")
    @Max(value = 50, message = "Chapter count must be <= 50")
    private Integer chapterCount;

    @Min(value = 500, message = "Target word count must be >= 500")
    @Max(value = 10000, message = "Target word count must be <= 10000")
    private Integer targetWordCount;

    @Min(value = 1, message = "Chapter no must be >= 1")
    private Integer chapterNo;

    private String chapterType;
}

