package com.starrynight.starrynight.system.knowledge.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("knowledge_library")
public class KnowledgeLibrary {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String name;

    private String type;

    private String description;

    private String tags;

    private String fileUrl;

    private String fileType;

    private Long fileSize;

    private Integer documentCount;

    private Integer chunkCount;

    private String status;

    private String errorMessage;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}