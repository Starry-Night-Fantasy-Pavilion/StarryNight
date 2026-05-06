package com.starrynight.starrynight.system.material.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("material_item")
public class MaterialItem {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long novelId;

    private String title;

    private String type;

    private String description;

    private String content;

    private String tags;

    private String source;

    private String sourceTool;

    private Integer usageCount;

    private LocalDateTime lastUsedAt;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}