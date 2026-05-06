package com.starrynight.starrynight.system.stylesample.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class StyleExpandRequestDTO {

    @NotBlank(message = "扩写文本不能为空")
    private String text;

    @NotBlank(message = "扩写风格不能为空")
    private String style;

    private Integer intensity;

    private Long sampleId;
}