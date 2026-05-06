package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.engine.consistency.ConsistencyReport;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.AiConsistencyCheckDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftGenerateDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandRequestDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandResultDTO;
import com.starrynight.starrynight.system.novel.dto.GenerateVolumesRequestDTO;
import com.starrynight.starrynight.system.novel.dto.GenerateOutlineRequestDTO;
import com.starrynight.starrynight.system.novel.dto.NovelVolumeDTO;
import com.starrynight.starrynight.system.novel.dto.NovelOutlineDTO;
import com.starrynight.starrynight.system.novel.dto.PlotSuggestionRequestDTO;
import com.starrynight.starrynight.system.novel.dto.PlotSuggestionResultDTO;
import com.starrynight.starrynight.system.novel.service.ChapterWorkshopService;
import com.starrynight.starrynight.system.novel.service.ChapterDraftService;
import com.starrynight.starrynight.system.novel.service.ContentExpandService;
import com.starrynight.starrynight.system.novel.service.NovelService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

/**
 * 对齐开发文档中的 AI 创作标准路由：
 * - /api/ai/generate-chapter-draft
 * - /api/ai/expand-content
 */
@RestController
@RequestMapping("/api/ai")
public class AiWritingController {

    private final ChapterDraftService chapterDraftService;
    private final ContentExpandService contentExpandService;
    private final ChapterWorkshopService chapterWorkshopService;
    private final NovelService novelService;

    public AiWritingController(ChapterDraftService chapterDraftService,
                               ContentExpandService contentExpandService,
                               ChapterWorkshopService chapterWorkshopService,
                               NovelService novelService) {
        this.chapterDraftService = chapterDraftService;
        this.contentExpandService = contentExpandService;
        this.chapterWorkshopService = chapterWorkshopService;
        this.novelService = novelService;
    }

    @PostMapping("/generate-volumes")
    public ResponseVO<List<NovelVolumeDTO>> generateVolumes(@Valid @RequestBody GenerateVolumesRequestDTO req) {
        return ResponseVO.success(novelService.generateVolumes(req.getNovelId(), req.getVolumeCount()));
    }

    @PostMapping("/generate-outline")
    public ResponseVO<NovelOutlineDTO> generateOutline(@Valid @RequestBody GenerateOutlineRequestDTO req) {
        return ResponseVO.success(novelService.generateOutline(req));
    }

    @PostMapping("/generate-chapter-draft")
    public ResponseVO<List<ChapterDraftDTO>> generateChapterDraft(@Valid @RequestBody ChapterDraftGenerateDTO req) {
        return ResponseVO.success(chapterDraftService.generate(req));
    }

    @PostMapping("/expand-content")
    public ResponseVO<ContentExpandResultDTO> expandContent(@Valid @RequestBody ContentExpandRequestDTO req) {
        return ResponseVO.success(contentExpandService.preview(req));
    }

    @PostMapping("/check-consistency")
    public ResponseVO<ConsistencyReport> checkConsistency(@Valid @RequestBody AiConsistencyCheckDTO req) {
        return ResponseVO.success(chapterWorkshopService.checkConsistency(req));
    }

    @PostMapping("/plot-suggestion")
    public ResponseVO<PlotSuggestionResultDTO> plotSuggestion(@Valid @RequestBody PlotSuggestionRequestDTO req) {
        return ResponseVO.success(chapterWorkshopService.suggestPlot(req));
    }
}

