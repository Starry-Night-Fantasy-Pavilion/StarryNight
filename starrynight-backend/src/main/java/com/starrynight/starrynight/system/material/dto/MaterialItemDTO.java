package com.starrynight.starrynight.system.material.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class MaterialItemDTO {

    private Long id;

    private Long novelId;

    @NotBlank(message = "素材标题不能为空")
    private String title;

    @NotBlank(message = "分类不能为空")
    private String type;

    private String description;

    private Object content;

    private String tags;

    private String source;

    private String sourceTool;

    private Integer usageCount;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}