package com.starrynight.starrynight.system.bookstore.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class BookstoreChapterMutateDTO {
    @NotNull
    private Integer chapterNo;

    @NotBlank
    private String title;

    private String content;
}
