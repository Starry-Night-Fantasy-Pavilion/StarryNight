package com.starrynight.starrynight.system.consistency.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("rhythm_analysis")
public class RhythmAnalysis {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private Long novelId;

    private Long chapterId;

    private Integer chapterNo;

    private String analysisType;

    private java.math.BigDecimal anticipationScore;

    private java.math.BigDecimal tensionScore;

    private java.math.BigDecimal warmthScore;

    private java.math.BigDecimal sadnessScore;

    private Integer conflictCount;

    private java.math.BigDecimal conflictDensity;

    private java.math.BigDecimal retentionScore;

    private String emotionCurve;

    private String conflictDetails;

    private String suggestions;

    private Integer wordCount;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}
