package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;

@Data
public class ChapterDraftConnectionIssueDTO {
    private Integer chapterNo;
    private String level;
    private String message;
    private String suggestion;
}

