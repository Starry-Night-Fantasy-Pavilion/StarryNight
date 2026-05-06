package com.starrynight.starrynight.system.novel.dto;

import com.starrynight.engine.consistency.ConsistencyReport;
import lombok.Builder;
import lombok.Data;

import java.util.List;

@Data
@Builder
public class ChapterWorkshopResultDTO {

    private String cPrompt;

    private List<String> recalledContext;

    private ConsistencyReport consistencyReport;

    private String generatedDraft;

    private List<String> generatedDrafts;

    private List<String> generatedDraftLabels;
}

