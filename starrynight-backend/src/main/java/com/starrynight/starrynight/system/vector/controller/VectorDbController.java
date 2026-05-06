package com.starrynight.starrynight.system.vector.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.vector.dto.*;
import com.starrynight.starrynight.system.vector.service.VectorDbService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/vector")
@RequiredArgsConstructor
public class VectorDbController {

    private final VectorDbService vectorDbService;

    @GetMapping("/stats")
    public ResponseVO<VectorStatsDTO> getStats() {
        return ResponseVO.success(vectorDbService.getStats());
    }

    @GetMapping("/nodes")
    public ResponseVO<List<VectorNodeDTO>> listNodes() {
        return ResponseVO.success(vectorDbService.listNodes());
    }

    @PostMapping("/nodes")
    public ResponseVO<VectorNodeDTO> createNode(@RequestBody VectorNodeDTO dto) {
        return ResponseVO.success(vectorDbService.createNode(dto));
    }

    @PutMapping("/nodes/{id}")
    public ResponseVO<VectorNodeDTO> updateNode(@PathVariable Long id, @RequestBody VectorNodeDTO dto) {
        return ResponseVO.success(vectorDbService.updateNode(id, dto));
    }

    @DeleteMapping("/nodes/{id}")
    public ResponseVO<Void> deleteNode(@PathVariable Long id) {
        vectorDbService.deleteNode(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/nodes/{id}/restart")
    public ResponseVO<Void> restartNode(@PathVariable Long id) {
        vectorDbService.restartNode(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/collections")
    public ResponseVO<List<VectorCollectionDTO>> listCollections() {
        return ResponseVO.success(vectorDbService.listCollections());
    }

    @PostMapping("/collections")
    public ResponseVO<VectorCollectionDTO> createCollection(@RequestBody VectorCollectionDTO dto) {
        return ResponseVO.success(vectorDbService.createCollection(dto));
    }

    @DeleteMapping("/collections/{id}")
    public ResponseVO<Void> deleteCollection(@PathVariable Long id) {
        vectorDbService.deleteCollection(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/collections/{id}/snapshot")
    public ResponseVO<Void> createSnapshot(@PathVariable Long id) {
        vectorDbService.createSnapshot(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/pool-config")
    public ResponseVO<VectorPoolConfigDTO> getPoolConfig() {
        return ResponseVO.success(vectorDbService.getPoolConfig());
    }

    @PutMapping("/pool-config")
    public ResponseVO<Void> savePoolConfig(@RequestBody VectorPoolConfigDTO config) {
        vectorDbService.savePoolConfig(config);
        return ResponseVO.success(null);
    }
}