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
@TableName("redeem_code")
public class RedeemCode {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String code;

    private String batchLabel;

    private String rewardType;

    private Long rewardPoints;

    private BigDecimal rewardCurrency;

    private Integer maxTotalRedemptions;

    private Integer redemptionCount;

    private Integer maxPerUser;

    private LocalDateTime validStart;

    private LocalDateTime validEnd;

    private Integer enabled;

    private Long campaignId;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
