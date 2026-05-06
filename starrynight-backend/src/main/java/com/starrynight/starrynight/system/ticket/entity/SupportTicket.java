package com.starrynight.starrynight.system.ticket.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableLogic;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("support_ticket")
public class SupportTicket {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String ticketNo;

    private Long userId;

    /** BUG / ACCOUNT / BILLING / CONTENT / FEATURE / OTHER */
    private String category;

    private String title;

    private String content;

    /** OPEN / IN_PROGRESS / RESOLVED / CLOSED */
    private String status;

    /** LOW / NORMAL / HIGH / URGENT */
    private String priority;

    private Long assignedTo;

    private String closeReason;

    private LocalDateTime resolvedAt;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}
