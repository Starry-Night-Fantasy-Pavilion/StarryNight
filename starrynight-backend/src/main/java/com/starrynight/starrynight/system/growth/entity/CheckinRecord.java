package com.starrynight.starrynight.system.growth.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDate;
import java.time.LocalDateTime;

@Data
@TableName("checkin_record")
public class CheckinRecord {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private LocalDate checkinDate;

    private LocalDateTime checkinTime;

    private String rewardType;

    private Long rewardAmount;

    private Integer continuousDays;

    private Integer isFirstCheckin;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
