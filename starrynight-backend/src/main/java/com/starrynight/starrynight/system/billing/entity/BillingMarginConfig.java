package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("billing_margin_config")
public class BillingMarginConfig {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String configType;

    private String configKey;

    private String contentType;

    private String userGroup;

    private BigDecimal profitMargin;

    private Integer enabled;

    private Integer priority;

    private LocalDateTime startTime;

    private LocalDateTime endTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
