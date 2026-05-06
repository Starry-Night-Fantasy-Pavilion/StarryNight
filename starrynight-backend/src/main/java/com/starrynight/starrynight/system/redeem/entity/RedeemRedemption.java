package com.starrynight.starrynight.system.redeem.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("redeem_redemption")
public class RedeemRedemption {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long redeemCodeId;

    private Long userId;

    private String rewardType;

    private Long pointsGranted;

    private BigDecimal currencyGranted;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
