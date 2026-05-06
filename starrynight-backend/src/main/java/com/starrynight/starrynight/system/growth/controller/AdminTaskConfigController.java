package com.starrynight.starrynight.system.growth.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.growth.dto.TaskConfigDTO;
import com.starrynight.starrynight.system.growth.service.AdminTaskConfigService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/growth/task-configs")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminTaskConfigController {

    private final AdminTaskConfigService adminTaskConfigService;

    @GetMapping("/list")
    public ResponseVO<PageVO<TaskConfigDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        return ResponseVO.success(adminTaskConfigService.page(keyword, page, size));
    }

    @GetMapping("/all")
    public ResponseVO<List<TaskConfigDTO>> all() {
        return ResponseVO.success(adminTaskConfigService.listAll());
    }

    @GetMapping("/{id}")
    public ResponseVO<TaskConfigDTO> get(@PathVariable Long id) {
        return ResponseVO.success(adminTaskConfigService.getById(id));
    }

    @PostMapping
    public ResponseVO<TaskConfigDTO> create(@Valid @RequestBody TaskConfigDTO dto) {
        return ResponseVO.success(adminTaskConfigService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<TaskConfigDTO> update(@PathVariable Long id, @Valid @RequestBody TaskConfigDTO dto) {
        return ResponseVO.success(adminTaskConfigService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        adminTaskConfigService.delete(id);
        return ResponseVO.success();
    }
}
