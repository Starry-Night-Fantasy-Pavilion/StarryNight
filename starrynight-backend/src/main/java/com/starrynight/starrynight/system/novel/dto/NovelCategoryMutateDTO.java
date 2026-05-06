package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class NovelCategoryMutateDTO {

    @NotBlank
    private String level1Name;

    /** 为空表示本条为一级分类 */
    private String level2Name;

    private Integer sort;

    private Integer status;
}
