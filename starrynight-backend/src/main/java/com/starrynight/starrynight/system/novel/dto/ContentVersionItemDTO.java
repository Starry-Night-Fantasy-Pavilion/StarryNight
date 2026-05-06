package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class ContentVersionItemDTO {
    private Long id;
    private Integer version;
    private String title;
    private String sourceType;
    private Integer wordCount;
    private LocalDateTime createTime;
    private String content;
}

