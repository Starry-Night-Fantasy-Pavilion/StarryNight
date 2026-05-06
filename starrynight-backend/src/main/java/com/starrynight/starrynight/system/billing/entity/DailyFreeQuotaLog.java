package com.starrynight.starrynight.system.billing.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Data
@TableName("daily_free_quota_log")
public class DailyFreeQuotaLog {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private LocalDate quotaDate;

    private Long grantedQuota;

    private String userGroup;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
