package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.AdminCommunityPostDTO;
import com.starrynight.starrynight.system.community.dto.CommunityRejectRequest;
import com.starrynight.starrynight.system.community.service.CommunityPostService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/community/posts")
@PreAuthorize("hasRole('ADMIN')")
public class AdminCommunityPostController {

    @Autowired
    private CommunityPostService communityPostService;

    @GetMapping("/list")
    public ResponseVO<PageVO<AdminCommunityPostDTO>> list(
            @RequestParam(required = false) Integer auditStatus,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<AdminCommunityPostDTO> p = communityPostService.adminList(auditStatus, page, size);
        return ResponseVO.success(PageVO.of(
                p.getTotal(),
                p.getRecords(),
                p.getCurrent(),
                p.getSize()));
    }

    @PostMapping("/{id}/approve")
    public ResponseVO<Void> approve(@PathVariable Long id) {
        communityPostService.adminApprove(id);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/reject")
    public ResponseVO<Void> reject(@PathVariable Long id, @RequestBody(required = false) CommunityRejectRequest body) {
        String reason = body != null ? body.getReason() : null;
        communityPostService.adminReject(id, reason);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/take-down")
    public ResponseVO<Void> takeDown(@PathVariable Long id) {
        communityPostService.adminTakeDown(id);
        return ResponseVO.success();
    }
}
