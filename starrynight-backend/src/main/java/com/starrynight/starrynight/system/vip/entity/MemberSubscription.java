package com.starrynight.starrynight.system.vip.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("member_subscription")
public class MemberSubscription {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long packageId;

    private Integer memberLevel;

    private LocalDateTime startTime;

    private LocalDateTime expireTime;

    private String status;

    private Integer autoRenew;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
