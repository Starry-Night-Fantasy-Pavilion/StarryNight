package com.starrynight.starrynight.system.novel.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("novel_volume")
public class NovelVolume {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long novelId;

    private String title;

    private String description;

    private Integer volumeOrder;

    private Integer chapterCount;

    private Integer wordCount;

    private Integer status;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}

