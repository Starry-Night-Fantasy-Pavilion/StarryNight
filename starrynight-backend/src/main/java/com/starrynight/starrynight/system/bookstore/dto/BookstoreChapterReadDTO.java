package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreChapterReadDTO {
    private Long bookId;
    private Integer chapterNo;
    private String title;
    /** 已规范化为可安全展示的 HTML 片段 */
    private String contentHtml;
    private Integer prevChapterNo;
    private Integer nextChapterNo;
    private Integer totalChapters;
}
