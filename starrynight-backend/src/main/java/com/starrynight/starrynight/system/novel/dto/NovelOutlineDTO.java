package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class NovelOutlineDTO {

    private Long id;

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    private Long volumeId;

    private Long chapterId;

    @NotBlank(message = "Type is required")
    private String type;

    @NotBlank(message = "Title is required")
    private String title;

    private String content;

    private Integer sortOrder;

    private Long parentId;

    private Integer version;
}

