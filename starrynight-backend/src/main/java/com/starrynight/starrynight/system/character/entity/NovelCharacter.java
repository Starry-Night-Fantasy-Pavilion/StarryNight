package com.starrynight.starrynight.system.character.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("novel_character")
public class NovelCharacter {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long novelId;

    private String name;

    private String identity;

    private String gender;

    private String age;

    private String appearance;

    private String background;

    private String motivation;

    private String personality;

    private String abilities;

    private String relationships;

    private String growthArc;

    private Integer sortOrder;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}