package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandRequestDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandResultDTO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionItemDTO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionRollbackDTO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionSaveDTO;
import com.starrynight.starrynight.system.novel.service.ContentExpandService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/novels/content-expand")
public class ContentExpandController {

    private final ContentExpandService contentExpandService;

    public ContentExpandController(ContentExpandService contentExpandService) {
        this.contentExpandService = contentExpandService;
    }

    @PostMapping("/preview")
    public ResponseVO<ContentExpandResultDTO> preview(@Valid @RequestBody ContentExpandRequestDTO req) {
        return ResponseVO.success(contentExpandService.preview(req));
    }

    @PostMapping("/versions/save")
    public ResponseVO<ContentVersionItemDTO> saveVersion(@Valid @RequestBody ContentVersionSaveDTO req) {
        return ResponseVO.success(contentExpandService.saveVersion(req));
    }

    @PostMapping("/versions/save-draft")
    public ResponseVO<ContentVersionItemDTO> saveDraftVersion(@Valid @RequestBody ContentVersionSaveDTO req) {
        return ResponseVO.success(contentExpandService.saveDraftVersion(req));
    }

    @GetMapping("/versions")
    public ResponseVO<List<ContentVersionItemDTO>> listVersions(@RequestParam Long chapterOutlineId) {
        return ResponseVO.success(contentExpandService.listVersions(chapterOutlineId));
    }

    @GetMapping("/versions/timeline")
    public ResponseVO<List<ContentVersionItemDTO>> listTimeline(@RequestParam Long chapterOutlineId) {
        return ResponseVO.success(contentExpandService.listTimeline(chapterOutlineId));
    }

    @PostMapping("/versions/rollback")
    public ResponseVO<ContentVersionItemDTO> rollback(@Valid @RequestBody ContentVersionRollbackDTO req) {
        return ResponseVO.success(contentExpandService.rollbackVersion(req.getVersionId()));
    }
}

