package com.starrynight.starrynight.system.knowledge.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeCapacityDTO;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeChunkDTO;
import com.starrynight.starrynight.system.knowledge.dto.KnowledgeLibraryDTO;
import com.starrynight.starrynight.system.knowledge.service.KnowledgeLibraryService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

@RestController
@RequestMapping("/api/knowledge")
public class KnowledgeLibraryController {

    @Autowired
    private KnowledgeLibraryService knowledgeLibraryService;

    // ==================== 知识库 CRUD ====================

    @GetMapping("/list")
    public ResponseVO<PageVO<KnowledgeLibraryDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) String type,
            @RequestParam(required = false) String status,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        return ResponseVO.success(knowledgeLibraryService.list(keyword, type, status, page, size));
    }

    @GetMapping("/{id}")
    public ResponseVO<KnowledgeLibraryDTO> get(@PathVariable Long id) {
        return ResponseVO.success(knowledgeLibraryService.getById(id));
    }

    @PostMapping
    public ResponseVO<KnowledgeLibraryDTO> create(@Valid @RequestBody KnowledgeLibraryDTO dto) {
        return ResponseVO.success(knowledgeLibraryService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<KnowledgeLibraryDTO> update(@PathVariable Long id, @Valid @RequestBody KnowledgeLibraryDTO dto) {
        return ResponseVO.success(knowledgeLibraryService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        knowledgeLibraryService.delete(id);
        return ResponseVO.success();
    }

    @GetMapping("/capacity")
    public ResponseVO<KnowledgeCapacityDTO> getCapacity() {
        return ResponseVO.success(knowledgeLibraryService.getCapacity());
    }

    // ==================== 文档上传与解析 ====================

    /**
     * 上传文档文件
     */
    @PostMapping(value = "/{id}/upload", consumes = "multipart/form-data")
    public ResponseVO<KnowledgeLibraryDTO> uploadDocument(
            @PathVariable Long id,
            @RequestParam("file") MultipartFile file) {
        return ResponseVO.success(knowledgeLibraryService.uploadDocument(id, file));
    }

    // ==================== 切片查询与检索 ====================

    /**
     * 获取知识库的切片列表
     */
    @GetMapping("/{id}/chunks")
    public ResponseVO<PageVO<KnowledgeChunkDTO>> listChunks(
            @PathVariable Long id,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        return ResponseVO.success(knowledgeLibraryService.listChunks(id, page, size));
    }

    /**
     * 在知识库内检索
     */
    @GetMapping("/{id}/search")
    public ResponseVO<PageVO<KnowledgeChunkDTO>> searchChunks(
            @PathVariable Long id,
            @RequestParam String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        return ResponseVO.success(knowledgeLibraryService.searchChunks(id, keyword, page, size));
    }

    /**
     * 跨知识库全局检索
     */
    @GetMapping("/search")
    public ResponseVO<PageVO<KnowledgeChunkDTO>> searchAll(
            @RequestParam String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        return ResponseVO.success(knowledgeLibraryService.searchAllChunks(keyword, page, size));
    }
}