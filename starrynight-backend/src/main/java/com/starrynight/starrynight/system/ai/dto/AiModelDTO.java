package com.starrynight.starrynight.system.ai.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class AiModelDTO {

    private Long id;

    @NotBlank(message = "Model code is required")
    private String modelCode;

    @NotBlank(message = "Model name is required")
    private String modelName;

    /** 仅回显兼容旧数据，新配置固定为 {@code default}，不再用于筛选 */
    private String modelType;

    private String provider;

    /** 关联计费渠道，与「计费配置 → 渠道管理」一致 */
    @NotNull(message = "请选择计费渠道")
    private Long billingChannelId;

    /** 回显：渠道编码 */
    private String channelCode;

    /** 回显：渠道名称 */
    private String channelName;

    @NotNull(message = "Enabled is required")
    private Integer enabled;

    private Integer sortOrder;
}
