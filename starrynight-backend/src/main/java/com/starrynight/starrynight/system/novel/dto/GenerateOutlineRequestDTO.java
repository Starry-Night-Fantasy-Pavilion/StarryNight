package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class GenerateOutlineRequestDTO {

    @NotNull(message = "Novel ID is required")
    private Long novelId;

    /**
     * 可选：一句话核心创意（优先于小说简介）
     */
    private String coreIdea;

    private String genre;

    private String style;

    /** 可选：大纲结构模板说明 */
    private String template;
}

