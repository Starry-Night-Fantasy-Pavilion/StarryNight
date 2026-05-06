package com.starrynight.starrynight.system.novel.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("novel")
public class Novel {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String title;

    private String subtitle;

    private String cover;

    private Long categoryId;

    private String genre;

    private String style;

    private String synopsis;

    private Integer wordCount;

    private Integer chapterCount;

    private Integer status;

    private Integer auditStatus;

    private Integer isPublished;

    private Integer isDeleted;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime publishTime;
}

