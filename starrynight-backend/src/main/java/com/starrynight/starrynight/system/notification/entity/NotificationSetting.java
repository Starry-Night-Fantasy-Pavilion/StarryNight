package com.starrynight.starrynight.system.notification.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("notification_setting")
public class NotificationSetting {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String notificationType;

    private Integer pushEnabled;

    private Integer emailEnabled;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
