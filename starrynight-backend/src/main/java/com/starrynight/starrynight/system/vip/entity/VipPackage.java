package com.starrynight.starrynight.system.vip.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("vip_package")
public class VipPackage {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String packageCode;

    private String packageName;

    private String description;

    private Integer memberLevel;

    private Integer durationDays;

    private BigDecimal price;

    private BigDecimal originalPrice;

    private Long dailyFreeQuota;

    private String features;

    private Integer sortOrder;

    private Integer status;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}
