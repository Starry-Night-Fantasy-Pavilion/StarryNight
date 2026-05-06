package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.CommunityPostCreateDTO;
import com.starrynight.starrynight.system.community.dto.CommunityPostPublicDTO;
import com.starrynight.starrynight.system.community.service.CommunityPostService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/community")
public class CommunityPostController {

    @Autowired
    private CommunityPostService communityPostService;

    @GetMapping("/post/list")
    public ResponseVO<PageVO<CommunityPostPublicDTO>> list(
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<CommunityPostPublicDTO> p = communityPostService.listPublic(page, size);
        return ResponseVO.success(PageVO.of(
                p.getTotal(),
                p.getRecords(),
                p.getCurrent(),
                p.getSize()));
    }

    @GetMapping("/post/{id}")
    public ResponseVO<CommunityPostPublicDTO> get(@PathVariable Long id) {
        return ResponseVO.success(communityPostService.getPublic(id));
    }

    @GetMapping("/author/post/{id}")
    public ResponseVO<CommunityPostPublicDTO> getForAuthor(@PathVariable Long id) {
        return ResponseVO.success(communityPostService.getForAuthor(id));
    }

    @PostMapping("/post")
    public ResponseVO<CommunityPostPublicDTO> create(@Valid @RequestBody CommunityPostCreateDTO body) {
        return ResponseVO.success(communityPostService.create(body));
    }

    @PutMapping("/post/{id}")
    public ResponseVO<CommunityPostPublicDTO> update(@PathVariable Long id, @Valid @RequestBody CommunityPostCreateDTO body) {
        return ResponseVO.success(communityPostService.updateOwn(id, body));
    }

    @DeleteMapping("/post/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        communityPostService.deleteOwn(id);
        return ResponseVO.success();
    }
}
