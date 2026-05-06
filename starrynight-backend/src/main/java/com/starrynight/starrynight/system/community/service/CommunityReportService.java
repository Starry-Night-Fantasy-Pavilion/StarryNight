package com.starrynight.starrynight.system.community.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.community.dto.AdminCommunityReportDTO;
import com.starrynight.starrynight.system.community.dto.CommunityReportCreateDTO;
import com.starrynight.starrynight.system.community.entity.CommunityComment;
import com.starrynight.starrynight.system.community.entity.CommunityPost;
import com.starrynight.starrynight.system.community.entity.CommunityReport;
import com.starrynight.starrynight.system.community.repository.CommunityCommentRepository;
import com.starrynight.starrynight.system.community.repository.CommunityPostRepository;
import com.starrynight.starrynight.system.community.repository.CommunityReportRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class CommunityReportService {

    public static final int STATUS_PENDING = 0;
    public static final int STATUS_RESOLVED = 1;
    public static final int STATUS_IGNORED = 2;

    public static final String KIND_POST = "POST";
    public static final String KIND_COMMENT = "COMMENT";

    public static final String ACTION_NONE = "NONE";
    public static final String ACTION_TAKE_DOWN_POST = "TAKE_DOWN_POST";
    public static final String ACTION_DELETE_COMMENT = "DELETE_COMMENT";

    @Autowired
    private CommunityReportRepository communityReportRepository;

    @Autowired
    private CommunityPostRepository communityPostRepository;

    @Autowired
    private CommunityCommentRepository communityCommentRepository;

    @Autowired
    private CommunityPostService communityPostService;

    @Autowired
    private CommunityCommentService communityCommentService;

    @Autowired
    private AuthUserRepository authUserRepository;

    @Transactional
    public void create(CommunityReportCreateDTO body) {
        communityPostService.requireEndUserForCommunityComment();
        Long reporterId = ThreadLocalUtil.getUserId();
        if (reporterId == null || reporterId <= 0) {
            throw new BusinessException("Please login");
        }
        String kind = StringUtils.hasText(body.getKind()) ? body.getKind().trim().toUpperCase() : "";
        String reason = body.getReason() == null ? "" : body.getReason().trim();
        String detail = body.getDetail() == null ? null : body.getDetail().trim();
        if (!StringUtils.hasText(reason)) {
            throw new BusinessException("Reason is required");
        }
        if (reason.length() > 50) {
            reason = reason.substring(0, 50);
        }
        if (detail != null && detail.length() > 500) {
            detail = detail.substring(0, 500);
        }

        CommunityReport e = new CommunityReport();
        e.setKind(kind);
        e.setReporterUserId(reporterId);
        e.setReason(reason);
        e.setDetail(detail);
        e.setStatus(STATUS_PENDING);
        e.setHandleAction(null);
        e.setHandleNote(null);
        e.setHandledBy(null);
        e.setHandledTime(null);

        if (KIND_POST.equals(kind)) {
            Long postId = body.getPostId();
            if (postId == null || postId <= 0) throw new BusinessException("postId is required");
            CommunityPost p = communityPostRepository.selectById(postId);
            if (p == null || (p.getDeleted() != null && p.getDeleted() != 0)) {
                throw new ResourceNotFoundException("Post not found");
            }
            if (reporterId.equals(p.getUserId())) {
                throw new BusinessException("Cannot report your own post");
            }
            e.setPostId(postId);
            e.setCommentId(null);
            e.setTargetUserId(p.getUserId());
        } else if (KIND_COMMENT.equals(kind)) {
            Long commentId = body.getCommentId();
            if (commentId == null || commentId <= 0) throw new BusinessException("commentId is required");
            CommunityComment c = communityCommentRepository.selectById(commentId);
            if (c == null || (c.getDeleted() != null && c.getDeleted() != 0)) {
                throw new ResourceNotFoundException("Comment not found");
            }
            if (reporterId.equals(c.getUserId())) {
                throw new BusinessException("Cannot report your own comment");
            }
            e.setPostId(c.getPostId());
            e.setCommentId(commentId);
            e.setTargetUserId(c.getUserId());
        } else {
            throw new BusinessException("Invalid kind");
        }

        communityReportRepository.insert(e);
    }

    public Page<AdminCommunityReportDTO> adminList(Integer status, int page, int size) {
        LambdaQueryWrapper<CommunityReport> q = new LambdaQueryWrapper<CommunityReport>()
                .orderByDesc(CommunityReport::getCreateTime);
        if (status != null) {
            q.eq(CommunityReport::getStatus, status);
        }
        Page<CommunityReport> p = communityReportRepository.selectPage(new Page<>(page, size), q);

        Set<Long> reporterIds = p.getRecords().stream().map(CommunityReport::getReporterUserId).collect(Collectors.toSet());
        Set<Long> targetIds = p.getRecords().stream().map(CommunityReport::getTargetUserId).collect(Collectors.toSet());
        Set<Long> postIds = p.getRecords().stream().map(CommunityReport::getPostId).collect(Collectors.toSet());
        Set<Long> commentIds = p.getRecords().stream()
                .map(CommunityReport::getCommentId)
                .filter(id -> id != null && id > 0)
                .collect(Collectors.toSet());

        Map<Long, String> reporterNames = loadUsernames(reporterIds);
        Map<Long, String> targetNames = loadUsernames(targetIds);
        Map<Long, CommunityPost> posts = loadPosts(postIds);
        Map<Long, CommunityComment> comments = loadComments(commentIds);

        Page<AdminCommunityReportDTO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream()
                .map(e -> toAdminDto(e, reporterNames, targetNames, posts, comments))
                .collect(Collectors.toList()));
        return out;
    }

    public long countPending() {
        Long c = communityReportRepository.selectCount(
                new LambdaQueryWrapper<CommunityReport>().eq(CommunityReport::getStatus, STATUS_PENDING));
        return c == null ? 0L : c;
    }

    @Transactional
    public void adminIgnore(Long id, String note) {
        CommunityReport e = requirePending(id);
        e.setStatus(STATUS_IGNORED);
        e.setHandleAction(ACTION_NONE);
        e.setHandleNote(StringUtils.hasText(note) ? trimTo(note, 500) : "忽略");
        e.setHandledBy(ThreadLocalUtil.getUserId());
        e.setHandledTime(LocalDateTime.now());
        communityReportRepository.updateById(e);
    }

    @Transactional
    public void adminResolve(Long id, String action, String note) {
        CommunityReport e = requirePending(id);
        String a = StringUtils.hasText(action) ? action.trim().toUpperCase() : ACTION_NONE;
        if (!ACTION_NONE.equals(a) && !ACTION_TAKE_DOWN_POST.equals(a) && !ACTION_DELETE_COMMENT.equals(a)) {
            throw new BusinessException("Invalid action");
        }
        if (ACTION_TAKE_DOWN_POST.equals(a)) {
            if (e.getPostId() == null) throw new BusinessException("Missing postId");
            communityPostService.adminTakeDown(e.getPostId());
        }
        if (ACTION_DELETE_COMMENT.equals(a)) {
            if (e.getCommentId() == null) throw new BusinessException("Missing commentId");
            communityCommentService.adminDelete(e.getCommentId());
        }
        e.setStatus(STATUS_RESOLVED);
        e.setHandleAction(a);
        e.setHandleNote(StringUtils.hasText(note) ? trimTo(note, 500) : null);
        e.setHandledBy(ThreadLocalUtil.getUserId());
        e.setHandledTime(LocalDateTime.now());
        communityReportRepository.updateById(e);
    }

    private CommunityReport requirePending(Long id) {
        CommunityReport e = communityReportRepository.selectById(id);
        if (e == null || (e.getDeleted() != null && e.getDeleted() != 0)) {
            throw new ResourceNotFoundException("Report not found");
        }
        if (e.getStatus() == null || e.getStatus() != STATUS_PENDING) {
            throw new BusinessException("Report is not pending");
        }
        return e;
    }

    private Map<Long, String> loadUsernames(Set<Long> userIds) {
        if (userIds == null || userIds.isEmpty()) return Map.of();
        return authUserRepository
                .selectList(new LambdaQueryWrapper<AuthUser>().in(AuthUser::getId, userIds))
                .stream()
                .collect(Collectors.toMap(AuthUser::getId, u -> u.getUsername() == null ? "" : u.getUsername()));
    }

    private Map<Long, CommunityPost> loadPosts(Set<Long> postIds) {
        if (postIds == null || postIds.isEmpty()) return Map.of();
        List<CommunityPost> list = communityPostRepository.selectList(
                new LambdaQueryWrapper<CommunityPost>().in(CommunityPost::getId, postIds));
        return list.stream().collect(Collectors.toMap(CommunityPost::getId, p -> p));
    }

    private Map<Long, CommunityComment> loadComments(Set<Long> commentIds) {
        if (commentIds == null || commentIds.isEmpty()) return Map.of();
        List<CommunityComment> list = communityCommentRepository.selectList(
                new LambdaQueryWrapper<CommunityComment>().in(CommunityComment::getId, commentIds));
        return list.stream().collect(Collectors.toMap(CommunityComment::getId, c -> c));
    }

    private static AdminCommunityReportDTO toAdminDto(
            CommunityReport e,
            Map<Long, String> reporterNames,
            Map<Long, String> targetNames,
            Map<Long, CommunityPost> posts,
            Map<Long, CommunityComment> comments) {
        AdminCommunityReportDTO d = new AdminCommunityReportDTO();
        d.setId(e.getId());
        d.setKind(e.getKind());
        d.setPostId(e.getPostId());
        d.setCommentId(e.getCommentId());
        d.setTargetUserId(e.getTargetUserId());
        d.setTargetUsername(targetNames.getOrDefault(e.getTargetUserId(), ""));
        d.setReporterUserId(e.getReporterUserId());
        d.setReporterUsername(reporterNames.getOrDefault(e.getReporterUserId(), ""));
        d.setReason(e.getReason());
        d.setDetail(e.getDetail());
        d.setStatus(e.getStatus());
        d.setHandleAction(e.getHandleAction());
        d.setHandleNote(e.getHandleNote());
        d.setHandledBy(e.getHandledBy());
        d.setHandledTime(e.getHandledTime());
        d.setCreateTime(e.getCreateTime());

        CommunityPost p = e.getPostId() == null ? null : posts.get(e.getPostId());
        if (p != null) {
            d.setPostTitle(StringUtils.hasText(p.getTitle()) ? p.getTitle().trim() : "（无标题）");
            if (KIND_POST.equals(e.getKind())) {
                d.setContentPreview(preview(p.getContent(), 200));
            }
        } else if (e.getPostId() != null) {
            d.setPostTitle("帖子#" + e.getPostId());
        }

        if (KIND_COMMENT.equals(e.getKind()) && e.getCommentId() != null) {
            CommunityComment c = comments.get(e.getCommentId());
            if (c != null) {
                d.setContentPreview(preview(c.getContent(), 200));
            }
        }
        return d;
    }

    private static String preview(String raw, int max) {
        if (!StringUtils.hasText(raw)) return "";
        String t = raw.trim();
        if (t.length() <= max) return t;
        return t.substring(0, max);
    }

    private static String trimTo(String s, int max) {
        String t = s.trim();
        if (t.length() <= max) return t;
        return t.substring(0, max);
    }
}

