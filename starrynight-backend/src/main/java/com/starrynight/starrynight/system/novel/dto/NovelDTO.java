package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class NovelDTO {

    private Long id;

    @NotBlank(message = "Title is required")
    @Size(max = 200, message = "Title too long")
    private String title;

    private String subtitle;

    private String cover;

    private Long categoryId;

    private String genre;

    private String style;

    @Size(max = 2000, message = "Synopsis too long")
    private String synopsis;

    private Integer status;

    private Integer isPublished;
}

