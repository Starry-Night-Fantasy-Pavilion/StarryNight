package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.AdminCommunityCommentDTO;
import com.starrynight.starrynight.system.community.dto.CommunityRejectRequest;
import com.starrynight.starrynight.system.community.service.CommunityCommentService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/community/comments")
@PreAuthorize("hasRole('ADMIN')")
public class AdminCommunityCommentController {

    @Autowired
    private CommunityCommentService communityCommentService;

    @GetMapping("/list")
    public ResponseVO<PageVO<AdminCommunityCommentDTO>> list(
            @RequestParam(required = false) Long postId,
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Integer auditStatus,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        Page<AdminCommunityCommentDTO> p =
                communityCommentService.adminList(postId, keyword, auditStatus, page, size);
        return ResponseVO.success(PageVO.of(
                p.getTotal(),
                p.getRecords(),
                p.getCurrent(),
                p.getSize()));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        communityCommentService.adminDelete(id);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/approve")
    public ResponseVO<Void> approve(@PathVariable Long id) {
        communityCommentService.adminApproveComment(id);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/reject")
    public ResponseVO<Void> reject(@PathVariable Long id, @RequestBody(required = false) CommunityRejectRequest body) {
        String reason = body != null ? body.getReason() : null;
        communityCommentService.adminRejectComment(id, reason);
        return ResponseVO.success();
    }
}
