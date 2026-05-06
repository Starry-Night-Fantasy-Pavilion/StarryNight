package com.starrynight.starrynight.system.redeem.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.redeem.dto.RedeemCodeDTO;
import com.starrynight.starrynight.system.redeem.dto.RedeemGenerateRequest;
import com.starrynight.starrynight.system.redeem.service.RedeemService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/redeem-codes")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminRedeemCodeController {

    private final RedeemService redeemService;

    @GetMapping("/list")
    public ResponseVO<PageVO<RedeemCodeDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        return ResponseVO.success(redeemService.page(keyword, page, size));
    }

    @PostMapping
    public ResponseVO<RedeemCodeDTO> create(@Valid @RequestBody RedeemCodeDTO dto) {
        return ResponseVO.success(redeemService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<RedeemCodeDTO> update(@PathVariable Long id, @Valid @RequestBody RedeemCodeDTO dto) {
        return ResponseVO.success(redeemService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        redeemService.delete(id);
        return ResponseVO.success();
    }

    @PostMapping("/generate")
    public ResponseVO<List<RedeemCodeDTO>> generate(@Valid @RequestBody RedeemGenerateRequest req) {
        return ResponseVO.success(redeemService.generate(req));
    }
}
