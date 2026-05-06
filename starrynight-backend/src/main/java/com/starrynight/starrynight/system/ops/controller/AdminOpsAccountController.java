package com.starrynight.starrynight.system.ops.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountCreateDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountPasswordDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountUpdateDTO;
import com.starrynight.starrynight.system.ops.service.OpsAccountService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/ops-accounts")
@PreAuthorize("hasRole('ADMIN')")
public class AdminOpsAccountController {

    @Autowired
    private OpsAccountService opsAccountService;

    @GetMapping("/list")
    public ResponseVO<PageVO<OpsAccountDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Integer status,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size
    ) {
        Page<OpsAccountDTO> pageData = opsAccountService.list(keyword, status, page, size);
        return ResponseVO.success(PageVO.of(
                pageData.getTotal(),
                pageData.getRecords(),
                pageData.getCurrent(),
                pageData.getSize()
        ));
    }

    @PostMapping
    public ResponseVO<OpsAccountDTO> create(@Valid @RequestBody OpsAccountCreateDTO dto) {
        return ResponseVO.success(opsAccountService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<OpsAccountDTO> update(@PathVariable Long id, @Valid @RequestBody OpsAccountUpdateDTO dto) {
        return ResponseVO.success(opsAccountService.update(id, dto));
    }

    @PutMapping("/{id}/password")
    public ResponseVO<Void> resetPassword(@PathVariable Long id, @Valid @RequestBody OpsAccountPasswordDTO dto) {
        opsAccountService.resetPassword(id, dto);
        return ResponseVO.success();
    }
}
