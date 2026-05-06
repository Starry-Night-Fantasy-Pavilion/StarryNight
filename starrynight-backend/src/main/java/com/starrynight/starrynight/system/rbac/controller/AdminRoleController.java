package com.starrynight.starrynight.system.rbac.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.rbac.dto.AdminRoleDTO;
import com.starrynight.starrynight.system.rbac.service.AdminRoleService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/roles")
@PreAuthorize("hasRole('ADMIN')")
public class AdminRoleController {

    @Autowired
    private AdminRoleService adminRoleService;

    @GetMapping("/list")
    public ResponseVO<List<AdminRoleDTO>> list(@RequestParam(required = false) Integer status) {
        return ResponseVO.success(adminRoleService.list(status));
    }

    @GetMapping("/{id}")
    public ResponseVO<AdminRoleDTO> get(@PathVariable Long id) {
        return ResponseVO.success(adminRoleService.getById(id));
    }

    @PostMapping
    public ResponseVO<AdminRoleDTO> create(@Valid @RequestBody AdminRoleDTO dto) {
        return ResponseVO.success(adminRoleService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<AdminRoleDTO> update(@PathVariable Long id, @Valid @RequestBody AdminRoleDTO dto) {
        return ResponseVO.success(adminRoleService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        adminRoleService.delete(id);
        return ResponseVO.success();
    }
}
