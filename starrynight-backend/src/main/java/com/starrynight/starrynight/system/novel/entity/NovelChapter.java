package com.starrynight.starrynight.system.novel.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("novel_chapter")
public class NovelChapter {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long novelId;

    private Long volumeId;

    private String title;

    private String content;

    private String outline;

    private Integer chapterOrder;

    private Integer wordCount;

    private Integer status;

    private Integer version;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime publishTime;
}

