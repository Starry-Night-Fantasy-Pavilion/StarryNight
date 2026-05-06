package com.starrynight.starrynight.system.community.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.conditions.update.LambdaUpdateWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.community.dto.AdminCommunityPostDTO;
import com.starrynight.starrynight.system.community.dto.CommunityLikeResultDTO;
import com.starrynight.starrynight.system.community.dto.CommunityPostCreateDTO;
import com.starrynight.starrynight.system.community.dto.CommunityPostPublicDTO;
import com.starrynight.starrynight.system.community.entity.CommunityPost;
import com.starrynight.starrynight.system.community.entity.CommunityPostLike;
import com.starrynight.starrynight.system.community.moderation.CommunityModerationScanner;
import com.starrynight.starrynight.system.community.repository.CommunityPostLikeRepository;
import com.starrynight.starrynight.system.community.repository.CommunityPostRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.authentication.AnonymousAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.Collections;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class CommunityPostService {

    public static final int AUDIT_PENDING = 0;
    public static final int AUDIT_APPROVED = 1;
    public static final int AUDIT_REJECTED = 2;

    @Autowired
    private CommunityPostRepository communityPostRepository;

    @Autowired
    private CommunityPostLikeRepository communityPostLikeRepository;

    @Autowired
    private AuthUserRepository authUserRepository;

    @Autowired
    private CommunityModerationScanner moderationScanner;

    /** 与发帖相同：仅用户端账号，不含运营 JWT */
    public void requireEndUserForCommunityComment() {
        requireEndUser();
    }

    /** 与 GET 帖子详情相同的可见性（不含浏览量自增） */
    public CommunityPost requirePostVisibleToCurrentViewer(Long postId) {
        return loadVisiblePostOrThrow(postId);
    }

    /** 仅已上架且审核通过的帖子可评论、点赞 */
    public void requirePostPublishedForInteraction(Long postId) {
        CommunityPost e = communityPostRepository.selectById(postId);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        if (e.getAuditStatus() == null || e.getAuditStatus() != AUDIT_APPROVED
                || e.getOnlineStatus() == null || e.getOnlineStatus() != 1) {
            throw new BusinessException("Only published posts support this action");
        }
    }

    @Transactional
    public CommunityLikeResultDTO toggleLike(Long postId) {
        requireEndUser();
        requirePostPublishedForInteraction(postId);
        Long uid = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<CommunityPostLike> w = new LambdaQueryWrapper<CommunityPostLike>()
                .eq(CommunityPostLike::getPostId, postId)
                .eq(CommunityPostLike::getUserId, uid);
        long exists = communityPostLikeRepository.selectCount(w);
        CommunityLikeResultDTO out = new CommunityLikeResultDTO();
        if (exists > 0) {
            communityPostLikeRepository.delete(w);
            adjustLikeCount(postId, -1);
            out.setLiked(false);
        } else {
            CommunityPostLike row = new CommunityPostLike();
            row.setPostId(postId);
            row.setUserId(uid);
            communityPostLikeRepository.insert(row);
            adjustLikeCount(postId, 1);
            out.setLiked(true);
        }
        CommunityPost p = communityPostRepository.selectById(postId);
        out.setLikeCount(p.getLikeCount() == null ? 0 : p.getLikeCount());
        return out;
    }

    public Page<CommunityPostPublicDTO> listPublic(int page, int size) {
        LambdaQueryWrapper<CommunityPost> q = new LambdaQueryWrapper<CommunityPost>()
                .eq(CommunityPost::getAuditStatus, AUDIT_APPROVED)
                .eq(CommunityPost::getOnlineStatus, 1)
                .orderByDesc(CommunityPost::getCreateTime);
        Page<CommunityPost> p = communityPostRepository.selectPage(new Page<>(page, size), q);
        Map<Long, String> names = loadUsernames(
                p.getRecords().stream().map(CommunityPost::getUserId).collect(Collectors.toSet()));
        Page<CommunityPostPublicDTO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream().map(e -> toPublicDto(e, names)).collect(Collectors.toList()));
        return out;
    }

    public CommunityPostPublicDTO getForAuthor(Long id) {
        requireEndUser();
        Long userId = ThreadLocalUtil.getUserId();
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        if (!userId.equals(e.getUserId())) {
            throw new BusinessException("Forbidden");
        }
        Map<Long, String> names = loadUsernames(Set.of(e.getUserId()));
        CommunityPostPublicDTO dto = toPublicDto(e, names);
        dto.setRejectReason(e.getRejectReason());
        applyLikedByMe(dto, e.getId());
        return dto;
    }

    public CommunityPostPublicDTO getPublic(Long id) {
        CommunityPost e = loadVisiblePostOrThrow(id);
        boolean approvedOnline = e.getAuditStatus() != null && e.getAuditStatus() == AUDIT_APPROVED
                && e.getOnlineStatus() != null && e.getOnlineStatus() == 1;
        Long viewerId = ThreadLocalUtil.getUserId();
        boolean authorPeek =
                isEndUserPrincipal() && viewerId != null && viewerId.equals(e.getUserId());
        Map<Long, String> names = loadUsernames(Set.of(e.getUserId()));
        CommunityPostPublicDTO dto = toPublicDto(e, names);
        if (approvedOnline) {
            e.setViewCount(e.getViewCount() == null ? 1 : e.getViewCount() + 1);
            communityPostRepository.updateById(e);
            dto.setViewCount(e.getViewCount());
        }
        if (authorPeek && !approvedOnline) {
            dto.setRejectReason(e.getRejectReason());
        }
        applyLikedByMe(dto, e.getId());
        return dto;
    }

    @Transactional
    public CommunityPostPublicDTO create(CommunityPostCreateDTO body) {
        requireEndUser();
        Long userId = ThreadLocalUtil.getUserId();
        CommunityPost e = new CommunityPost();
        e.setUserId(userId);
        e.setTitle(StringUtils.hasText(body.getTitle()) ? body.getTitle().trim() : null);
        e.setContent(body.getContent().trim());
        e.setContentType(StringUtils.hasText(body.getContentType()) ? body.getContentType().trim() : "text");
        e.setTopicId(body.getTopicId());
        CommunityModerationScanner.ScanResult sr = moderationScanner.scanPost(e.getTitle(), e.getContent());
        applyScanVerdictToPost(e, sr);
        e.setLikeCount(0);
        e.setCommentCount(0);
        e.setViewCount(0);
        e.setOnlineStatus(1);
        communityPostRepository.insert(e);
        Map<Long, String> names = loadUsernames(Set.of(userId));
        CommunityPostPublicDTO dto = toPublicDto(e, names);
        applyLikedByMe(dto, e.getId());
        return dto;
    }

    @Transactional
    public CommunityPostPublicDTO updateOwn(Long id, CommunityPostCreateDTO body) {
        requireEndUser();
        Long userId = ThreadLocalUtil.getUserId();
        CommunityPost e = requireOwnedPost(id, userId);
        if (e.getAuditStatus() != AUDIT_PENDING && e.getAuditStatus() != AUDIT_REJECTED) {
            throw new BusinessException("Only pending or rejected posts can be edited");
        }
        e.setTitle(StringUtils.hasText(body.getTitle()) ? body.getTitle().trim() : null);
        e.setContent(body.getContent().trim());
        e.setContentType(StringUtils.hasText(body.getContentType()) ? body.getContentType().trim() : "text");
        e.setTopicId(body.getTopicId());
        CommunityModerationScanner.ScanResult sr = moderationScanner.scanPost(e.getTitle(), e.getContent());
        applyScanVerdictToPost(e, sr);
        communityPostRepository.updateById(e);
        Map<Long, String> names = loadUsernames(Set.of(userId));
        CommunityPostPublicDTO dto = toPublicDto(e, names);
        applyLikedByMe(dto, e.getId());
        return dto;
    }

    @Transactional
    public void deleteOwn(Long id) {
        requireEndUser();
        Long userId = ThreadLocalUtil.getUserId();
        CommunityPost e = requireOwnedPost(id, userId);
        communityPostRepository.deleteById(e.getId());
    }

    public Page<AdminCommunityPostDTO> adminList(Integer auditStatus, int page, int size) {
        LambdaQueryWrapper<CommunityPost> q = new LambdaQueryWrapper<CommunityPost>()
                .orderByDesc(CommunityPost::getCreateTime);
        if (auditStatus != null) {
            q.eq(CommunityPost::getAuditStatus, auditStatus);
        }
        Page<CommunityPost> p = communityPostRepository.selectPage(new Page<>(page, size), q);
        Map<Long, String> names = loadUsernames(
                p.getRecords().stream().map(CommunityPost::getUserId).collect(Collectors.toSet()));
        Page<AdminCommunityPostDTO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream().map(e -> toAdminDto(e, names)).collect(Collectors.toList()));
        return out;
    }

    @Transactional
    public void adminApprove(Long id) {
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        e.setAuditStatus(AUDIT_APPROVED);
        e.setRejectReason(null);
        e.setOnlineStatus(1);
        communityPostRepository.updateById(e);
    }

    @Transactional
    public void adminReject(Long id, String reason) {
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        e.setAuditStatus(AUDIT_REJECTED);
        e.setRejectReason(StringUtils.hasText(reason) ? reason.trim() : null);
        communityPostRepository.updateById(e);
    }

    @Transactional
    public void adminTakeDown(Long id) {
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        e.setOnlineStatus(0);
        communityPostRepository.updateById(e);
    }

    private void applyScanVerdictToPost(CommunityPost e, CommunityModerationScanner.ScanResult sr) {
        switch (sr.getVerdict()) {
            case PASS:
                e.setAuditStatus(AUDIT_APPROVED);
                e.setRejectReason(null);
                break;
            case NEEDS_REVIEW:
                e.setAuditStatus(AUDIT_PENDING);
                e.setRejectReason("自动审核：待人工复核（敏感词：" + sr.getMatchedWord() + "）");
                break;
            case BLOCKED:
                e.setAuditStatus(AUDIT_REJECTED);
                e.setRejectReason("自动审核：内容包含高风险敏感信息，未予公开展示");
                break;
            default:
                e.setAuditStatus(AUDIT_PENDING);
                e.setRejectReason("自动审核异常");
                break;
        }
    }

    private CommunityPost loadVisiblePostOrThrow(Long id) {
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        boolean approvedOnline = e.getAuditStatus() != null && e.getAuditStatus() == AUDIT_APPROVED
                && e.getOnlineStatus() != null && e.getOnlineStatus() == 1;
        Long viewerId = ThreadLocalUtil.getUserId();
        boolean authorPeek =
                isEndUserPrincipal() && viewerId != null && viewerId.equals(e.getUserId());
        if (!approvedOnline && !authorPeek) {
            throw new ResourceNotFoundException("Post not found");
        }
        return e;
    }

    private void adjustLikeCount(long postId, int delta) {
        String sql = delta > 0 ? "like_count = like_count + 1" : "like_count = GREATEST(like_count - 1, 0)";
        communityPostRepository.update(
                null,
                new LambdaUpdateWrapper<CommunityPost>().eq(CommunityPost::getId, postId).setSql(sql));
    }

    private void applyLikedByMe(CommunityPostPublicDTO dto, Long postId) {
        if (!isEndUserPrincipal()) {
            dto.setLikedByMe(false);
            return;
        }
        Long uid = ThreadLocalUtil.getUserId();
        if (uid == null) {
            dto.setLikedByMe(false);
            return;
        }
        long c = communityPostLikeRepository.selectCount(
                new LambdaQueryWrapper<CommunityPostLike>()
                        .eq(CommunityPostLike::getPostId, postId)
                        .eq(CommunityPostLike::getUserId, uid));
        dto.setLikedByMe(c > 0);
    }

    private CommunityPost requireOwnedPost(Long id, Long userId) {
        CommunityPost e = communityPostRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Post not found");
        }
        if (!userId.equals(e.getUserId())) {
            throw new BusinessException("Forbidden");
        }
        return e;
    }

    private void requireEndUser() {
        Authentication auth = SecurityContextHolder.getContext().getAuthentication();
        if (auth == null || !auth.isAuthenticated() || auth instanceof AnonymousAuthenticationToken) {
            throw new BusinessException("Unauthorized");
        }
        for (GrantedAuthority a : auth.getAuthorities()) {
            if ("ROLE_ADMIN".equals(a.getAuthority())) {
                throw new BusinessException("Community posts require a user account token");
            }
        }
    }

    /** 当前请求为用户端 JWT（非运营端 ADMIN） */
    private boolean isEndUserPrincipal() {
        Authentication auth = SecurityContextHolder.getContext().getAuthentication();
        if (auth == null || !auth.isAuthenticated() || auth instanceof AnonymousAuthenticationToken) {
            return false;
        }
        for (GrantedAuthority a : auth.getAuthorities()) {
            if ("ROLE_ADMIN".equals(a.getAuthority())) {
                return false;
            }
        }
        return true;
    }

    private Map<Long, String> loadUsernames(Set<Long> userIds) {
        if (userIds == null || userIds.isEmpty()) {
            return Collections.emptyMap();
        }
        return authUserRepository
                .selectList(new LambdaQueryWrapper<AuthUser>().in(AuthUser::getId, userIds))
                .stream()
                .collect(Collectors.toMap(AuthUser::getId, u -> u.getUsername() == null ? "" : u.getUsername()));
    }

    private static CommunityPostPublicDTO toPublicDto(CommunityPost e, Map<Long, String> names) {
        CommunityPostPublicDTO d = new CommunityPostPublicDTO();
        d.setId(e.getId());
        d.setUserId(e.getUserId());
        d.setAuthorUsername(names.getOrDefault(e.getUserId(), ""));
        d.setTitle(e.getTitle());
        d.setContent(e.getContent());
        d.setContentType(e.getContentType());
        d.setTopicId(e.getTopicId());
        d.setLikeCount(e.getLikeCount());
        d.setCommentCount(e.getCommentCount());
        d.setViewCount(e.getViewCount());
        d.setAuditStatus(e.getAuditStatus());
        d.setRejectReason(null);
        d.setInteractionEnabled(
                e.getAuditStatus() != null && e.getAuditStatus() == AUDIT_APPROVED
                        && e.getOnlineStatus() != null && e.getOnlineStatus() == 1);
        d.setCreateTime(e.getCreateTime());
        return d;
    }

    private static AdminCommunityPostDTO toAdminDto(CommunityPost e, Map<Long, String> names) {
        AdminCommunityPostDTO d = new AdminCommunityPostDTO();
        d.setId(e.getId());
        d.setUserId(e.getUserId());
        d.setAuthorUsername(names.getOrDefault(e.getUserId(), ""));
        d.setTitle(e.getTitle());
        d.setContent(e.getContent());
        d.setContentType(e.getContentType());
        d.setTopicId(e.getTopicId());
        d.setAuditStatus(e.getAuditStatus());
        d.setRejectReason(e.getRejectReason());
        d.setLikeCount(e.getLikeCount());
        d.setCommentCount(e.getCommentCount());
        d.setViewCount(e.getViewCount());
        d.setOnlineStatus(e.getOnlineStatus());
        d.setCreateTime(e.getCreateTime());
        d.setUpdateTime(e.getUpdateTime());
        return d;
    }
}
