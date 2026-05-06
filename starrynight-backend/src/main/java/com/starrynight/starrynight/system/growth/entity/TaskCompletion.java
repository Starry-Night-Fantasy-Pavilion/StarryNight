package com.starrynight.starrynight.system.growth.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDate;
import java.time.LocalDateTime;

@Data
@TableName("task_completion")
public class TaskCompletion {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long taskId;

    private String taskCode;

    private LocalDate completionDate;

    private Integer completionCount;

    private Integer rewardClaimed;

    private LocalDateTime rewardClaimTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
