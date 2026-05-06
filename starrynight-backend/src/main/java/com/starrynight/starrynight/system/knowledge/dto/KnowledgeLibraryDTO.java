package com.starrynight.starrynight.system.knowledge.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class KnowledgeLibraryDTO {

    private Long id;

    @NotBlank(message = "知识库名称不能为空")
    private String name;

    @NotBlank(message = "分类不能为空")
    private String type;

    private String description;

    private String tags;

    private String fileUrl;

    private String fileType;

    private Long fileSize;

    private Integer documentCount;

    private Integer chunkCount;

    private String status;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}