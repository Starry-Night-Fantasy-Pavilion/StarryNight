package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class NovelVolumeDTO {

    private Long id;

    private Long novelId;

    @NotBlank(message = "Title is required")
    private String title;

    private String description;

    private Integer volumeOrder;

    private Integer chapterCount;

    private Integer wordCount;

    private Integer status;
}

