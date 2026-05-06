package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class ContinueWritingRequestDTO {

    /**
     * 当前编辑器内容（作为续写的输入基底）
     */
    @NotBlank(message = "sourceContent is required")
    private String sourceContent;

    /**
     * 续写强度/扩写比例（复用扩写 ratio 语义）
     */
    @Min(value = 1, message = "expandRatio must be >= 1")
    @Max(value = 5, message = "expandRatio must be <= 5")
    private Integer expandRatio;

    /**
     * 可选：风格样本（用于简单风格指纹分析）
     */
    private String styleSample;

    /**
     * 可选：是否进行衔接优化（跨段过渡句）
     */
    private Boolean optimizeConnections;

    /**
     * 可选：是否开启后处理（换行与空白规范化）
     */
    private Boolean postProcessEnabled;

    /**
     * 可选：作品 ID；传入时会在续写前注入该作品的向量记忆召回（novelId 隔离）。
     */
    private Long novelId;
}

