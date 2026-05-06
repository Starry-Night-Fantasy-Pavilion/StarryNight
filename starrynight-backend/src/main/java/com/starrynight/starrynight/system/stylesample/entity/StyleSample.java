package com.starrynight.starrynight.system.stylesample.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("style_sample")
public class StyleSample {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String name;

    private String content;

    private String styleLabel;

    private String styleFingerprint;

    private Integer wordCount;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}