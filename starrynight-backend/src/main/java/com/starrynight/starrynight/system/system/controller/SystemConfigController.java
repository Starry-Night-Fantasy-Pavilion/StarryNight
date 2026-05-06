package com.starrynight.starrynight.system.system.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.system.dto.SystemConfigDTO;
import com.starrynight.starrynight.system.system.service.SystemConfigService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/admin/config")
@PreAuthorize("hasRole('ADMIN')")
public class SystemConfigController {

    @Autowired
    private SystemConfigService systemConfigService;

    @GetMapping("/list")
    public ResponseVO<List<SystemConfigDTO>> list(@RequestParam(required = false) String group) {
        return ResponseVO.success(systemConfigService.listByGroup(group));
    }

    @GetMapping("/key/{key}")
    public ResponseVO<SystemConfigDTO> get(@PathVariable String key) {
        return ResponseVO.success(systemConfigService.getByKey(key));
    }

    @GetMapping("/value/{key}")
    public ResponseVO<String> getValue(@PathVariable String key) {
        return ResponseVO.success(systemConfigService.getValue(key));
    }

    @GetMapping("/grouped")
    public ResponseVO<Map<String, String>> getGroupedConfigs() {
        return ResponseVO.success(systemConfigService.getGroupedConfigs());
    }

    @PostMapping
    public ResponseVO<SystemConfigDTO> create(@Valid @RequestBody SystemConfigDTO dto) {
        return ResponseVO.success(systemConfigService.create(dto));
    }

    @PutMapping
    public ResponseVO<SystemConfigDTO> update(@Valid @RequestBody SystemConfigDTO dto) {
        return ResponseVO.success(systemConfigService.update(dto));
    }

    @DeleteMapping("/{key}")
    public ResponseVO<Void> delete(@PathVariable String key) {
        systemConfigService.delete(key);
        return ResponseVO.success();
    }

    /**
     * 直改 {@code system_config} 或跑脚本后调用：重载运行时快照并执行热切换逻辑（需 ADMIN）。
     */
    @PostMapping("/reload-runtime")
    public ResponseVO<Void> reloadRuntimeSnapshot() {
        systemConfigService.reloadRuntimeSnapshot();
        return ResponseVO.success();
    }
}

