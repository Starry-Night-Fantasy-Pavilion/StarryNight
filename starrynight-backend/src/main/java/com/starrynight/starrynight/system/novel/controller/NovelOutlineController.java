package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.NovelOutlineDTO;
import com.starrynight.starrynight.system.novel.service.NovelService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/novels/outlines")
public class NovelOutlineController {

    @Autowired
    private NovelService novelService;

    @GetMapping
    public ResponseVO<List<NovelOutlineDTO>> list(
            @RequestParam Long novelId,
            @RequestParam String type,
            @RequestParam(required = false) Long volumeId,
            @RequestParam(required = false) Long chapterId) {
        return ResponseVO.success(novelService.listOutlines(novelId, type, volumeId, chapterId));
    }

    @PostMapping
    public ResponseVO<NovelOutlineDTO> upsert(@Valid @RequestBody NovelOutlineDTO dto) {
        return ResponseVO.success(novelService.upsertOutline(dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        novelService.deleteOutline(id);
        return ResponseVO.success();
    }
}

