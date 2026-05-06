package com.starrynight.starrynight.system.community.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableLogic;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("community_report")
public class CommunityReport {

    @TableId(type = IdType.AUTO)
    private Long id;

    /** POST / COMMENT */
    private String kind;

    private Long postId;

    private Long commentId;

    /** 被举报对象作者（帖子/评论作者） */
    private Long targetUserId;

    /** 举报人 auth_user.id */
    private Long reporterUserId;

    /** 举报原因（简短） */
    private String reason;

    /** 详细说明（可选） */
    private String detail;

    /** 0 待处理 1 已处理 2 已忽略 */
    private Integer status;

    /** 处理动作：NONE / TAKE_DOWN_POST / DELETE_COMMENT */
    private String handleAction;

    private String handleNote;

    /** 处理人（运营账号/管理员，当前系统用 ThreadLocal userId 兼容） */
    private Long handledBy;

    private LocalDateTime handledTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}

