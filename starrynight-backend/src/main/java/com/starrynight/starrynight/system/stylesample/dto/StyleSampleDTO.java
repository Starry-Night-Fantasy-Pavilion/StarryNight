package com.starrynight.starrynight.system.stylesample.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class StyleSampleDTO {

    private Long id;

    @NotBlank(message = "样本名称不能为空")
    private String name;

    @NotBlank(message = "样本内容不能为空")
    private String content;

    private String styleLabel;

    private Object styleFingerprint;

    private Integer wordCount;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}