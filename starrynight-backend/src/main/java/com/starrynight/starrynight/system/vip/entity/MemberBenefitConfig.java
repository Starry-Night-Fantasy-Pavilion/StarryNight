package com.starrynight.starrynight.system.vip.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("member_benefit_config")
public class MemberBenefitConfig {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Integer memberLevel;

    private String benefitKey;

    private String benefitName;

    private String benefitValue;

    private String description;

    private Integer enabled;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
