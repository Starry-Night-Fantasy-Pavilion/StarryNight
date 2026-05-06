package com.starrynight.starrynight.system.consistency.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("character_consistency")
public class CharacterConsistency {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long novelId;

    private Long characterId;

    private String consistencyType;

    private Long checkChapterId;

    private String checkResult;

    private String issueDescription;

    private String suggestion;

    private String severity;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
