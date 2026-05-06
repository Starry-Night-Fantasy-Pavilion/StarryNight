package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.CommunityCommentCreateDTO;
import com.starrynight.starrynight.system.community.dto.CommunityCommentDTO;
import com.starrynight.starrynight.system.community.dto.CommunityLikeResultDTO;
import com.starrynight.starrynight.system.community.service.CommunityCommentService;
import com.starrynight.starrynight.system.community.service.CommunityPostService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/community")
public class CommunityInteractionController {

    @Autowired
    private CommunityCommentService communityCommentService;

    @Autowired
    private CommunityPostService communityPostService;

    @GetMapping("/post/{postId}/comments")
    public ResponseVO<PageVO<CommunityCommentDTO>> listComments(
            @PathVariable Long postId,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        Page<CommunityCommentDTO> p = communityCommentService.listByPost(postId, page, size);
        return ResponseVO.success(PageVO.of(
                p.getTotal(),
                p.getRecords(),
                p.getCurrent(),
                p.getSize()));
    }

    @PostMapping("/comment")
    public ResponseVO<CommunityCommentDTO> createComment(@Valid @RequestBody CommunityCommentCreateDTO body) {
        return ResponseVO.success(communityCommentService.create(body));
    }

    @DeleteMapping("/comment/{id}")
    public ResponseVO<Void> deleteComment(@PathVariable Long id) {
        communityCommentService.deleteOwn(id);
        return ResponseVO.success();
    }

    @PostMapping("/post/{postId}/like")
    public ResponseVO<CommunityLikeResultDTO> toggleLike(@PathVariable Long postId) {
        return ResponseVO.success(communityPostService.toggleLike(postId));
    }
}
