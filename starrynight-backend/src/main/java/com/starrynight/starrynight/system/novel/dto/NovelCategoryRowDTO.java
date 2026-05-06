package com.starrynight.starrynight.system.novel.dto;

import lombok.Builder;
import lombok.Data;

@Data
@Builder
public class NovelCategoryRowDTO {

    private Long id;

    private Long parentId;

    private String level1Name;

    /** 一级分类时为空或「—」 */
    private String level2Name;

    private Integer sort;

    private Integer status;

    private Long novelCount;

    private Long bookCount;
}
