package com.starrynight.starrynight.system.novel.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.GenerateOutlineRequestDTO;
import com.starrynight.starrynight.system.novel.dto.NovelChapterDTO;
import com.starrynight.starrynight.system.novel.dto.NovelDTO;
import com.starrynight.starrynight.system.novel.dto.NovelOutlineDTO;
import com.starrynight.starrynight.system.novel.dto.NovelVolumeDTO;
import com.starrynight.starrynight.system.novel.service.NovelService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/novels")
public class NovelController {

    @Autowired
    private NovelService novelService;

    @GetMapping
    public ResponseVO<PageVO<NovelDTO>> list(
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<NovelDTO> pageData = novelService.listUserNovels(page, size);
        return ResponseVO.success(PageVO.of(
                pageData.getTotal(),
                pageData.getRecords(),
                pageData.getCurrent(),
                pageData.getSize()
        ));
    }

    @GetMapping("/{id}")
    public ResponseVO<NovelDTO> get(@PathVariable Long id) {
        return ResponseVO.success(novelService.getById(id));
    }

    @PostMapping
    public ResponseVO<NovelDTO> create(@Valid @RequestBody NovelDTO dto) {
        return ResponseVO.success(novelService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<NovelDTO> update(@PathVariable Long id, @Valid @RequestBody NovelDTO dto) {
        return ResponseVO.success(novelService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        novelService.delete(id);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/publish")
    public ResponseVO<Void> publish(@PathVariable Long id) {
        novelService.publish(id);
        return ResponseVO.success();
    }

    @GetMapping("/{novelId}/volumes")
    public ResponseVO<List<NovelVolumeDTO>> listVolumes(@PathVariable Long novelId) {
        return ResponseVO.success(novelService.listVolumes(novelId));
    }

    @PostMapping("/volumes")
    public ResponseVO<NovelVolumeDTO> createVolume(@Valid @RequestBody NovelVolumeDTO dto) {
        return ResponseVO.success(novelService.createVolume(dto));
    }

    @PutMapping("/volumes/{id}")
    public ResponseVO<NovelVolumeDTO> updateVolume(@PathVariable Long id, @Valid @RequestBody NovelVolumeDTO dto) {
        dto.setId(id);
        return ResponseVO.success(novelService.updateVolume(id, dto));
    }

    @DeleteMapping("/volumes/{id}")
    public ResponseVO<Void> deleteVolume(@PathVariable Long id) {
        novelService.deleteVolume(id);
        return ResponseVO.success();
    }

    @GetMapping("/{novelId}/chapters")
    public ResponseVO<List<NovelChapterDTO>> listChapters(
            @PathVariable Long novelId,
            @RequestParam(required = false) Long volumeId) {
        return ResponseVO.success(novelService.listChapters(novelId, volumeId));
    }

    @GetMapping("/chapters/{id}")
    public ResponseVO<NovelChapterDTO> getChapter(@PathVariable Long id) {
        return ResponseVO.success(novelService.getChapter(id));
    }

    @PostMapping("/chapters")
    public ResponseVO<NovelChapterDTO> createChapter(@Valid @RequestBody NovelChapterDTO dto) {
        return ResponseVO.success(novelService.createChapter(dto));
    }

    @PutMapping("/chapters/{id}")
    public ResponseVO<NovelChapterDTO> updateChapter(@PathVariable Long id, @Valid @RequestBody NovelChapterDTO dto) {
        return ResponseVO.success(novelService.updateChapter(id, dto));
    }

    @DeleteMapping("/chapters/{id}")
    public ResponseVO<Void> deleteChapter(@PathVariable Long id) {
        novelService.deleteChapter(id);
        return ResponseVO.success();
    }

    @GetMapping("/{id}/export")
    public ResponseVO<String> exportNovel(@PathVariable Long id, @RequestParam(defaultValue = "txt") String format) {
        String exportedContent = novelService.exportNovel(id, format);
        return ResponseVO.success(exportedContent);
    }

    @GetMapping("/{id}/export/word")
    public ResponseEntity<byte[]> exportNovelToWord(@PathVariable Long id) {
        byte[] wordContent = novelService.exportNovelToWord(id);
        String filename = "novel_" + id + ".docx";
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.parseMediaType("application/vnd.openxmlformats-officedocument.wordprocessingml.document"));
        headers.setContentDispositionFormData("attachment", filename);
        headers.setContentLength(wordContent.length);
        return ResponseEntity.ok().headers(headers).body(wordContent);
    }

    @PostMapping("/{novelId}/generate-outline")
    public ResponseVO<NovelOutlineDTO> generateOutline(@PathVariable Long novelId,
                                                       @RequestBody(required = false) GenerateOutlineRequestDTO req) {
        if (req == null) {
            req = new GenerateOutlineRequestDTO();
        }
        req.setNovelId(novelId);
        return ResponseVO.success(novelService.generateOutline(req));
    }

    @PostMapping("/{novelId}/generate-volumes")
    public ResponseVO<List<NovelVolumeDTO>> generateVolumes(@PathVariable Long novelId,
                                                            @RequestParam(required = false) Integer volumeCount) {
        return ResponseVO.success(novelService.generateVolumes(novelId, volumeCount));
    }
}

