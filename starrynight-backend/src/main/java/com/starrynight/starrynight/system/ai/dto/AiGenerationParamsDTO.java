package com.starrynight.starrynight.system.ai.dto;

import lombok.Data;

/** 运营端「生成参数」配置，持久化为 system_config 中单条 JSON。 */
@Data
public class AiGenerationParamsDTO {

    private Double temperature;

    private Integer maxTokens;

    private Double topP;

    private Double frequencyPenalty;

    private Double presencePenalty;

    private Double outlineTemperature;

    private Double contentTemperature;

    private Double chatTemperature;

    private Boolean enableStreaming;

    private Integer streamInterval;
}
