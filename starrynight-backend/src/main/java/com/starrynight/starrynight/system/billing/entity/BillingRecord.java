package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("billing_record")
public class BillingRecord {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String recordNo;

    private Long userId;

    private Long channelId;

    private String contentType;

    private Long contentId;

    private Integer inputTokens;

    private Integer outputTokens;

    private Integer totalTokens;

    private BigDecimal channelCost;

    private BigDecimal profitMargin;

    private BigDecimal userPrice;

    private Integer creationPoints;

    private Integer freePointsUsed;

    private Integer paidPointsUsed;

    private BigDecimal platformCurrencyUsed;

    private Integer generationSuccess;

    private String errorMessage;

    private String rollbackRecordNo;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
