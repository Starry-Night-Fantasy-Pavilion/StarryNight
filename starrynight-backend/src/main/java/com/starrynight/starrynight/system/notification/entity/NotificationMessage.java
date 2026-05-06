package com.starrynight.starrynight.system.notification.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("notification_message")
public class NotificationMessage {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String notificationType;

    private String title;

    private String content;

    private String linkUrl;

    private String linkParams;

    private Integer isRead;

    private LocalDateTime readTime;

    private String priority;

    private LocalDateTime expireTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
