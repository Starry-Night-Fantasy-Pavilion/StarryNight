package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Data
@TableName("user_balance")
public class UserBalance {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long freeQuota;

    private LocalDate freeQuotaDate;

    private BigDecimal platformCurrency;

    private Integer enableMixedPayment;

    private Long totalFreeUsed;

    private Long totalPaidUsed;

    private Long totalRecharged;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
