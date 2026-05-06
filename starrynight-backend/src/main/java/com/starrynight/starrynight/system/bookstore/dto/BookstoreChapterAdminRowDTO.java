package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreChapterAdminRowDTO {
    private Long id;
    private Integer chapterNo;
    private String title;
    private Integer wordCount;
}
