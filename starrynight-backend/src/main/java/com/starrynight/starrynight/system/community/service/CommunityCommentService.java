package com.starrynight.starrynight.system.community.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.conditions.update.LambdaUpdateWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.community.dto.AdminCommunityCommentDTO;
import com.starrynight.starrynight.system.community.dto.CommunityCommentCreateDTO;
import com.starrynight.starrynight.system.community.dto.CommunityCommentDTO;
import com.starrynight.starrynight.system.community.entity.CommunityComment;
import com.starrynight.starrynight.system.community.entity.CommunityPost;
import com.starrynight.starrynight.system.community.moderation.CommunityModerationScanner;
import com.starrynight.starrynight.system.community.repository.CommunityCommentRepository;
import com.starrynight.starrynight.system.community.repository.CommunityPostRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class CommunityCommentService {

    public static final int COMMENT_PENDING = 0;
    public static final int COMMENT_APPROVED = 1;
    public static final int COMMENT_REJECTED = 2;

    @Autowired
    private CommunityCommentRepository communityCommentRepository;

    @Autowired
    private CommunityPostRepository communityPostRepository;

    @Autowired
    private CommunityPostService communityPostService;

    @Autowired
    private AuthUserRepository authUserRepository;

    @Autowired
    private CommunityModerationScanner moderationScanner;

    public Page<CommunityCommentDTO> listByPost(long postId, int page, int size) {
        communityPostService.requirePostVisibleToCurrentViewer(postId);
        Long viewerId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<CommunityComment> q = new LambdaQueryWrapper<CommunityComment>()
                .eq(CommunityComment::getPostId, postId);
        if (viewerId != null && viewerId > 0) {
            final Long uid = viewerId;
            q.and(w -> w.eq(CommunityComment::getAuditStatus, COMMENT_APPROVED)
                    .or(sub -> sub.eq(CommunityComment::getUserId, uid)
                            .in(CommunityComment::getAuditStatus, COMMENT_PENDING, COMMENT_REJECTED)));
        } else {
            q.eq(CommunityComment::getAuditStatus, COMMENT_APPROVED);
        }
        q.orderByAsc(CommunityComment::getCreateTime);
        Page<CommunityComment> p = communityCommentRepository.selectPage(new Page<>(page, size), q);
        Map<Long, String> names = loadUsernames(
                p.getRecords().stream().map(CommunityComment::getUserId).collect(Collectors.toSet()));
        Page<CommunityCommentDTO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream().map(e -> toDto(e, names)).collect(Collectors.toList()));
        return out;
    }

    public Page<AdminCommunityCommentDTO> adminList(
            Long postId, String keyword, Integer auditStatus, int page, int size) {
        LambdaQueryWrapper<CommunityComment> q = new LambdaQueryWrapper<CommunityComment>()
                .orderByDesc(CommunityComment::getCreateTime);
        if (postId != null && postId > 0) {
            q.eq(CommunityComment::getPostId, postId);
        }
        if (auditStatus != null) {
            q.eq(CommunityComment::getAuditStatus, auditStatus);
        }
        if (StringUtils.hasText(keyword)) {
            q.like(CommunityComment::getContent, keyword.trim());
        }
        Page<CommunityComment> p = communityCommentRepository.selectPage(new Page<>(page, size), q);
        Set<Long> userIds = p.getRecords().stream().map(CommunityComment::getUserId).collect(Collectors.toSet());
        Set<Long> postIds = p.getRecords().stream().map(CommunityComment::getPostId).collect(Collectors.toSet());
        Map<Long, String> userNames = loadUsernames(userIds);
        Map<Long, String> postTitles = loadPostTitles(postIds);
        Page<AdminCommunityCommentDTO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream()
                .map(e -> toAdminDto(e, userNames, postTitles))
                .collect(Collectors.toList()));
        return out;
    }

    @Transactional
    public void adminDelete(Long id) {
        CommunityComment e = requireActiveComment(id);
        softDeleteComment(e);
    }

    @Transactional
    public void adminApproveComment(Long id) {
        CommunityComment e = requireCommentPending(id);
        e.setAuditStatus(COMMENT_APPROVED);
        e.setModerationNote(null);
        communityCommentRepository.updateById(e);
        incrementPostCommentCount(e.getPostId());
    }

    @Transactional
    public void adminRejectComment(Long id, String reason) {
        CommunityComment e = requireCommentPending(id);
        e.setAuditStatus(COMMENT_REJECTED);
        e.setModerationNote(StringUtils.hasText(reason) ? reason.trim() : "人工驳回");
        communityCommentRepository.updateById(e);
    }

    @Transactional
    public CommunityCommentDTO create(CommunityCommentCreateDTO body) {
        communityPostService.requireEndUserForCommunityComment();
        Long userId = ThreadLocalUtil.getUserId();
        communityPostService.requirePostPublishedForInteraction(body.getPostId());
        Long parentId = body.getParentId();
        if (parentId != null && parentId > 0) {
            CommunityComment parent = communityCommentRepository.selectById(parentId);
            if (parent == null || (parent.getDeleted() != null && parent.getDeleted() != 0)) {
                throw new ResourceNotFoundException("Parent comment not found");
            }
            if (!parent.getPostId().equals(body.getPostId())) {
                throw new BusinessException("Parent comment does not belong to this post");
            }
            if (parent.getAuditStatus() == null || parent.getAuditStatus() != COMMENT_APPROVED) {
                throw new BusinessException("Cannot reply until parent comment is approved");
            }
        }
        CommunityModerationScanner.ScanResult sr = moderationScanner.scanComment(body.getContent());
        CommunityComment e = new CommunityComment();
        e.setPostId(body.getPostId());
        e.setUserId(userId);
        e.setParentId(parentId != null && parentId > 0 ? parentId : null);
        e.setContent(body.getContent().trim());
        switch (sr.getVerdict()) {
            case PASS:
                e.setAuditStatus(COMMENT_APPROVED);
                e.setModerationNote(null);
                break;
            case NEEDS_REVIEW:
                e.setAuditStatus(COMMENT_PENDING);
                e.setModerationNote("自动审核：待人工复核（敏感词：" + sr.getMatchedWord() + "）");
                break;
            case BLOCKED:
                throw new BusinessException("内容未通过自动审核，请修改后重试");
            default:
                throw new BusinessException("审核异常");
        }
        communityCommentRepository.insert(e);
        if (e.getAuditStatus() != null && e.getAuditStatus() == COMMENT_APPROVED) {
            incrementPostCommentCount(body.getPostId());
        }
        Map<Long, String> names = loadUsernames(Set.of(userId));
        return toDto(e, names);
    }

    @Transactional
    public void deleteOwn(Long id) {
        communityPostService.requireEndUserForCommunityComment();
        Long userId = ThreadLocalUtil.getUserId();
        CommunityComment e = requireActiveComment(id);
        if (!userId.equals(e.getUserId())) {
            throw new BusinessException("Forbidden");
        }
        softDeleteComment(e);
    }

    private CommunityComment requireActiveComment(Long id) {
        CommunityComment e = communityCommentRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Comment not found");
        }
        return e;
    }

    private CommunityComment requireCommentPending(Long id) {
        CommunityComment e = requireActiveComment(id);
        if (e.getAuditStatus() == null || e.getAuditStatus() != COMMENT_PENDING) {
            throw new BusinessException("Comment is not pending review");
        }
        return e;
    }

    private void softDeleteComment(CommunityComment e) {
        boolean wasApproved = e.getAuditStatus() != null && e.getAuditStatus() == COMMENT_APPROVED;
        communityCommentRepository.deleteById(e.getId());
        if (wasApproved) {
            decrementPostCommentCount(e.getPostId());
        }
    }

    private void incrementPostCommentCount(Long postId) {
        communityPostRepository.update(
                null,
                new LambdaUpdateWrapper<CommunityPost>()
                        .eq(CommunityPost::getId, postId)
                        .setSql("comment_count = comment_count + 1"));
    }

    private void decrementPostCommentCount(Long postId) {
        communityPostRepository.update(
                null,
                new LambdaUpdateWrapper<CommunityPost>()
                        .eq(CommunityPost::getId, postId)
                        .setSql("comment_count = GREATEST(comment_count - 1, 0)"));
    }

    private Map<Long, String> loadPostTitles(Set<Long> postIds) {
        if (postIds == null || postIds.isEmpty()) {
            return Map.of();
        }
        List<CommunityPost> posts = communityPostRepository.selectList(
                new LambdaQueryWrapper<CommunityPost>().in(CommunityPost::getId, postIds));
        Map<Long, String> map = posts.stream()
                .collect(Collectors.toMap(
                        CommunityPost::getId,
                        p -> StringUtils.hasText(p.getTitle()) ? p.getTitle().trim() : "（无标题）"));
        for (Long pid : postIds) {
            map.putIfAbsent(pid, "帖子#" + pid);
        }
        return map;
    }

    private static AdminCommunityCommentDTO toAdminDto(
            CommunityComment e,
            Map<Long, String> userNames,
            Map<Long, String> postTitles) {
        AdminCommunityCommentDTO d = new AdminCommunityCommentDTO();
        d.setId(e.getId());
        d.setPostId(e.getPostId());
        d.setPostTitle(postTitles.getOrDefault(e.getPostId(), "帖子#" + e.getPostId()));
        d.setUserId(e.getUserId());
        d.setAuthorUsername(userNames.getOrDefault(e.getUserId(), ""));
        d.setParentId(e.getParentId());
        d.setContent(e.getContent());
        d.setCreateTime(e.getCreateTime());
        d.setAuditStatus(e.getAuditStatus());
        d.setModerationNote(e.getModerationNote());
        return d;
    }

    private Map<Long, String> loadUsernames(Set<Long> userIds) {
        if (userIds == null || userIds.isEmpty()) {
            return Map.of();
        }
        return authUserRepository
                .selectList(new LambdaQueryWrapper<AuthUser>().in(AuthUser::getId, userIds))
                .stream()
                .collect(Collectors.toMap(AuthUser::getId, u -> u.getUsername() == null ? "" : u.getUsername()));
    }

    private static CommunityCommentDTO toDto(CommunityComment e, Map<Long, String> names) {
        CommunityCommentDTO d = new CommunityCommentDTO();
        d.setId(e.getId());
        d.setPostId(e.getPostId());
        d.setUserId(e.getUserId());
        d.setAuthorUsername(names.getOrDefault(e.getUserId(), ""));
        d.setParentId(e.getParentId());
        d.setContent(e.getContent());
        d.setCreateTime(e.getCreateTime());
        d.setAuditStatus(e.getAuditStatus());
        d.setModerationNote(e.getModerationNote());
        return d;
    }
}
