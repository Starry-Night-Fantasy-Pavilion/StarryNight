package com.starrynight.starrynight.system.novel.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("novel_outline")
public class NovelOutline {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long novelId;

    private Long volumeId;

    private Long chapterId;

    private String type;

    private String title;

    private String content;

    private Integer sortOrder;

    private Long parentId;

    private Integer version;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}

