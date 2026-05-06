package com.starrynight.starrynight.system.knowledge.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("knowledge_chunk")
public class KnowledgeChunk {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long libraryId;

    private String content;

    private String contentHash;

    private Integer chunkOrder;

    private Integer tokenCount;

    private String vectorId;

    private String metadata;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}