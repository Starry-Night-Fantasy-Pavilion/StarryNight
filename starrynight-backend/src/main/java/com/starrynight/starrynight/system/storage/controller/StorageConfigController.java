package com.starrynight.starrynight.system.storage.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.storage.dto.StorageConfigDTO;
import com.starrynight.starrynight.system.storage.service.StorageConfigService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/storage")
@RequiredArgsConstructor
public class StorageConfigController {

    private final StorageConfigService storageConfigService;

    @GetMapping("/configs")
    public ResponseVO<List<StorageConfigDTO>> listConfigs() {
        return ResponseVO.success(storageConfigService.listConfigs());
    }

    @GetMapping("/configs/{id}")
    public ResponseVO<StorageConfigDTO> getConfig(@PathVariable Long id) {
        return ResponseVO.success(storageConfigService.getConfig(id));
    }

    @GetMapping("/configs/default")
    public ResponseVO<StorageConfigDTO> getDefaultConfig() {
        return ResponseVO.success(storageConfigService.getDefaultConfig());
    }

    @PostMapping("/configs")
    public ResponseVO<StorageConfigDTO> createConfig(@RequestBody StorageConfigDTO dto) {
        return ResponseVO.success(storageConfigService.createConfig(dto));
    }

    @PutMapping("/configs/{id}")
    public ResponseVO<StorageConfigDTO> updateConfig(@PathVariable Long id, @RequestBody StorageConfigDTO dto) {
        return ResponseVO.success(storageConfigService.updateConfig(id, dto));
    }

    @DeleteMapping("/configs/{id}")
    public ResponseVO<Void> deleteConfig(@PathVariable Long id) {
        storageConfigService.deleteConfig(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/configs/{id}/test")
    public ResponseVO<Void> testConnection(@PathVariable Long id) {
        storageConfigService.testConnection(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/stats")
    public ResponseVO<StorageConfigDTO> getStats() {
        return ResponseVO.success(storageConfigService.getStats());
    }
}