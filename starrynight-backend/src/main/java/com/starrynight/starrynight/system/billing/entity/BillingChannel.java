package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("billing_channel")
public class BillingChannel {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String channelCode;

    private String channelName;

    private String channelType;

    private String apiBaseUrl;

    private String apiKey;

    private String modelName;

    private BigDecimal costPer1kInput;

    private BigDecimal costPer1kOutput;

    private BigDecimal costPerCall;

    private BigDecimal costPerSecond;

    private BigDecimal baseCost;

    private Integer isFree;

    private String status;

    private Integer failureCount;

    private LocalDateTime lastFailureTime;

    private LocalDateTime circuitOpenTime;

    private Integer enabled;

    private Integer sortOrder;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}
