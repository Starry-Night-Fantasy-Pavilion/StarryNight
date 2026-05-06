package com.starrynight.starrynight.system.growth.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("points_transaction")
public class PointsTransaction {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String transactionType;

    private Integer pointsChange;

    private Long balanceBefore;

    private Long balanceAfter;

    private Long sourceId;

    private String description;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
