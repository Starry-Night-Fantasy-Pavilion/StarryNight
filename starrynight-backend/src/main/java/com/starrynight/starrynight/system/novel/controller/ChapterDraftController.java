package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftConnectionCheckDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftConnectionIssueDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftDTO;
import com.starrynight.starrynight.system.novel.dto.ChapterDraftGenerateDTO;
import com.starrynight.starrynight.system.novel.service.ChapterDraftService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/novels/chapter-drafts")
public class ChapterDraftController {

    private final ChapterDraftService chapterDraftService;

    public ChapterDraftController(ChapterDraftService chapterDraftService) {
        this.chapterDraftService = chapterDraftService;
    }

    @PostMapping("/generate")
    public ResponseVO<List<ChapterDraftDTO>> generate(@Valid @RequestBody ChapterDraftGenerateDTO req) {
        return ResponseVO.success(chapterDraftService.generate(req));
    }

    @PostMapping("/check-connections")
    public ResponseVO<List<ChapterDraftConnectionIssueDTO>> checkConnections(@Valid @RequestBody ChapterDraftConnectionCheckDTO req) {
        return ResponseVO.success(chapterDraftService.checkConnections(req));
    }
}

