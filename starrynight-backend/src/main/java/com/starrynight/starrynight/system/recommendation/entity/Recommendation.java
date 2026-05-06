package com.starrynight.starrynight.system.recommendation.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;
import java.time.LocalDateTime;

@Data
@TableName("t_recommendation")
public class Recommendation {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String title;

    private String type;

    private Long novelId;

    private String novelTitle;

    private String cover;

    private String position;

    private Integer sort;

    private LocalDateTime startTime;

    private LocalDateTime endTime;

    private Integer status;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}