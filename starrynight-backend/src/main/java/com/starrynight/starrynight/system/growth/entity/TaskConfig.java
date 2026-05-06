package com.starrynight.starrynight.system.growth.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("task_config")
public class TaskConfig {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String taskCode;

    private String taskName;

    private String taskType;

    private String description;

    private String triggerAction;

    private String rewardType;

    private Long rewardAmount;

    private Integer conditionValue;

    private String conditionOperator;

    private Integer maxDailyTimes;

    private Integer sortOrder;

    private Integer enabled;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
