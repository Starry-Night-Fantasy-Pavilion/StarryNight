package com.starrynight.starrynight.system.growth.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class TaskConfigDTO {

    private Long id;

    @NotBlank
    private String taskCode;

    @NotBlank
    private String taskName;

    @NotBlank
    private String taskType;

    private String description;

    private String triggerAction;

    @NotBlank
    private String rewardType;

    @NotNull
    private Long rewardAmount;

    private Integer conditionValue;

    private String conditionOperator;

    private Integer maxDailyTimes;

    @NotNull
    private Integer sortOrder;

    @NotNull
    private Integer enabled;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}
