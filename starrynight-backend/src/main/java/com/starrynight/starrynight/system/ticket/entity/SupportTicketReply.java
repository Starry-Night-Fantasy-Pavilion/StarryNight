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
@TableName("support_ticket_reply")
public class SupportTicketReply {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long ticketId;

    /** USER / OPS */
    private String authorType;

    private Long authorId;

    private String authorName;

    private String content;

    /** 0-用户可见 1-仅运营可见 */
    private Integer isInternal;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}
