package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class NovelChapterDTO {

    private Long id;

    private Long novelId;

    private Long volumeId;

    @NotBlank(message = "Title is required")
    private String title;

    private String content;

    private String outline;

    private Integer chapterOrder;

    private Integer wordCount;

    private Integer status;

    private Integer version;
}

