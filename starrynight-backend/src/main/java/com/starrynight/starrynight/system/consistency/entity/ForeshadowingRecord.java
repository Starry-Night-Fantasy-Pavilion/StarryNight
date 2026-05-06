package com.starrynight.starrynight.system.consistency.entity;

import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("foreshadowing_record")
public class ForeshadowingRecord {

    @TableId(type = IdType.ASSIGN_UUID)
    private String id;

    private Long novelId;

    private Integer chapterNo;

    private String setupContent;

    private Float setupLocation;

    private String type;

    private String status;

    private Integer expectedChapterNo;

    private Integer autoDetectedExpected;

    private Float confidence;

    private LocalDateTime detectedAt;

    private LocalDateTime confirmedAt;

    private Boolean userEdited;

    private LocalDateTime paidOffAt;

    private Integer paidOffChapterNo;

    private String payoffMethod;

    private String payoffContent;

    private LocalDateTime createdAt;

    private LocalDateTime updatedAt;
}