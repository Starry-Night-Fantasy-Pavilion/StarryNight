package com.starrynight.starrynight.system.prompt.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class PromptTemplateDTO {

    private Long id;

    @NotBlank(message = "模板名称不能为空")
    private String name;

    @NotBlank(message = "分类不能为空")
    private String category;

    private String description;

    @NotBlank(message = "提示词模板不能为空")
    private String promptTemplate;

    private Object variables;

    private String outputFormat;

    private Boolean isBuiltin;

    private Integer version;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}