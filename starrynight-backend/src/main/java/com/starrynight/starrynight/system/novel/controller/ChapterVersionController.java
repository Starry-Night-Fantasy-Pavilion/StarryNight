package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.ContentVersionItemDTO;
import com.starrynight.starrynight.system.novel.service.ContentExpandService;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/novels/{novelId}/chapters")
public class ChapterVersionController {

    private final ContentExpandService contentExpandService;

    public ChapterVersionController(ContentExpandService contentExpandService) {
        this.contentExpandService = contentExpandService;
    }

    @GetMapping("/{chapterId}/version")
    public ResponseVO<List<ContentVersionItemDTO>> listTimeline(@PathVariable Long novelId,
                                                                 @PathVariable Long chapterId) {
        return ResponseVO.success(contentExpandService.listTimelineForChapter(novelId, chapterId));
    }

    @PostMapping("/{chapterId}/rollback/{vid}")
    public ResponseVO<ContentVersionItemDTO> rollback(@PathVariable Long novelId,
                                                       @PathVariable Long chapterId,
                                                       @PathVariable Long vid) {
        return ResponseVO.success(contentExpandService.rollbackVersionForChapter(novelId, chapterId, vid));
    }
}

