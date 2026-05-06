package com.starrynight.starrynight.system.campaign.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableLogic;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("ops_campaign")
public class OpsCampaign {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String title;

    private String summary;

    private String linkUrl;

    private String coverUrl;

    /** 0 草稿 1 已发布 2 已结束 */
    private Integer status;

    private LocalDateTime startTime;

    private LocalDateTime endTime;

    private Integer sortOrder;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}
