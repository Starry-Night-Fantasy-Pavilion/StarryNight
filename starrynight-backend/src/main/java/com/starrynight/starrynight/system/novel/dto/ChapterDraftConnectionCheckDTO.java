package com.starrynight.starrynight.system.novel.dto;

import jakarta.validation.constraints.NotEmpty;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class ChapterDraftConnectionCheckDTO {

    @NotNull(message = "Volume ID is required")
    private Long volumeId;

    @NotEmpty(message = "Drafts are required")
    private List<ChapterDraftDTO> drafts = new ArrayList<>();
}

