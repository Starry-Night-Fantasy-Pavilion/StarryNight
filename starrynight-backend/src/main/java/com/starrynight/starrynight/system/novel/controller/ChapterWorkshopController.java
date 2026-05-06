package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.ChapterWorkshopIntentDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterWorkshopResultDTO;
import com.starrynight.starrynight.system.novel.service.ChapterWorkshopService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/novels/workshop")
public class ChapterWorkshopController {

    private final ChapterWorkshopService chapterWorkshopService;

    public ChapterWorkshopController(ChapterWorkshopService chapterWorkshopService) {
        this.chapterWorkshopService = chapterWorkshopService;
    }

    @PostMapping("/chapter-preview")
    public ResponseVO<ChapterWorkshopResultDTO> chapterPreview(@Valid @RequestBody ChapterWorkshopIntentDTO dto) {
        return ResponseVO.success(chapterWorkshopService.preview(dto));
    }

    @PostMapping("/chapter-generate")
    public ResponseVO<ChapterWorkshopResultDTO> chapterGenerate(@Valid @RequestBody ChapterWorkshopIntentDTO dto) {
        return ResponseVO.success(chapterWorkshopService.generateDraft(dto));
    }
}

