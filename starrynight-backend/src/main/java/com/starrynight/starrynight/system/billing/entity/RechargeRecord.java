package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("recharge_record")
public class RechargeRecord {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String recordNo;

    private Long userId;

    private BigDecimal amount;

    private BigDecimal platformCurrency;

    private BigDecimal bonusCurrency;

    private String payMethod;

    private String payStatus;

    private LocalDateTime payTime;

    private String transactionId;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
