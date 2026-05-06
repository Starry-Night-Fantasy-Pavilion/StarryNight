package com.starrynight.starrynight.system.consistency.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("ai_consistency_check")
public class AiConsistencyCheck {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long novelId;

    private Long chapterId;

    private String contentType;

    private String checkType;

    private String checkResult;

    private Integer issueCount;

    private String issuesDetail;

    private String aiModel;

    private Integer processingTimeMs;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
