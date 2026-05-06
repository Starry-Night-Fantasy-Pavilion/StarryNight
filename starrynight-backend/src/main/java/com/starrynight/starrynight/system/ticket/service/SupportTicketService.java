package com.starrynight.starrynight.system.ticket.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.ticket.dto.AdminTicketUpdateDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketCreateDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketReplyDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketReplyVO;
import com.starrynight.starrynight.system.ticket.dto.TicketVO;
import com.starrynight.starrynight.system.ticket.entity.SupportTicket;
import com.starrynight.starrynight.system.ticket.entity.SupportTicketReply;
import com.starrynight.starrynight.system.ticket.repository.SupportTicketReplyRepository;
import com.starrynight.starrynight.system.ticket.repository.SupportTicketRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.atomic.AtomicLong;
import java.util.stream.Collectors;

@Service
public class SupportTicketService {

    public static final String STATUS_OPEN = "OPEN";
    public static final String STATUS_IN_PROGRESS = "IN_PROGRESS";
    public static final String STATUS_RESOLVED = "RESOLVED";
    public static final String STATUS_CLOSED = "CLOSED";

    public static final String AUTHOR_USER = "USER";
    public static final String AUTHOR_OPS = "OPS";

    private static final List<String> VALID_CATEGORIES =
            List.of("BUG", "ACCOUNT", "BILLING", "CONTENT", "FEATURE", "OTHER");
    private static final List<String> VALID_STATUSES =
            List.of(STATUS_OPEN, STATUS_IN_PROGRESS, STATUS_RESOLVED, STATUS_CLOSED);
    private static final List<String> VALID_PRIORITIES =
            List.of("LOW", "NORMAL", "HIGH", "URGENT");

    private static final AtomicLong SEQ = new AtomicLong(System.currentTimeMillis() % 100000);

    @Autowired
    private SupportTicketRepository ticketRepository;

    @Autowired
    private SupportTicketReplyRepository replyRepository;

    @Autowired
    private AuthUserRepository authUserRepository;

    @Autowired
    private OpsAccountRepository opsAccountRepository;

    // ─── 用户端 ───────────────────────────────────────────────

    @Transactional
    public TicketVO userCreate(TicketCreateDTO dto) {
        Long userId = requireUserId();
        String category = dto.getCategory().trim().toUpperCase();
        if (!VALID_CATEGORIES.contains(category)) {
            throw new BusinessException("无效的工单分类");
        }
        SupportTicket ticket = new SupportTicket();
        ticket.setTicketNo(generateTicketNo());
        ticket.setUserId(userId);
        ticket.setCategory(category);
        ticket.setTitle(dto.getTitle().trim());
        ticket.setContent(dto.getContent().trim());
        ticket.setStatus(STATUS_OPEN);
        ticket.setPriority("NORMAL");
        ticketRepository.insert(ticket);
        return toVO(ticket, false);
    }

    public Page<TicketVO> userList(String status, int page, int size) {
        Long userId = requireUserId();
        LambdaQueryWrapper<SupportTicket> q = new LambdaQueryWrapper<SupportTicket>()
                .eq(SupportTicket::getUserId, userId)
                .orderByDesc(SupportTicket::getCreateTime);
        if (StringUtils.hasText(status)) {
            q.eq(SupportTicket::getStatus, status.toUpperCase());
        }
        Page<SupportTicket> p = ticketRepository.selectPage(new Page<>(page, size), q);
        Page<TicketVO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream()
                .map(t -> toVO(t, false))
                .collect(Collectors.toList()));
        return out;
    }

    public TicketVO userGet(Long ticketId) {
        Long userId = requireUserId();
        SupportTicket ticket = requireTicket(ticketId);
        if (!userId.equals(ticket.getUserId())) {
            throw new BusinessException("无权访问该工单");
        }
        TicketVO vo = toVO(ticket, false);
        vo.setReplies(loadReplies(ticketId, false));
        return vo;
    }

    @Transactional
    public void userReply(Long ticketId, TicketReplyDTO dto) {
        Long userId = requireUserId();
        SupportTicket ticket = requireTicket(ticketId);
        if (!userId.equals(ticket.getUserId())) {
            throw new BusinessException("无权访问该工单");
        }
        if (STATUS_CLOSED.equals(ticket.getStatus())) {
            throw new BusinessException("工单已关闭，无法继续回复");
        }
        AuthUser user = authUserRepository.selectById(userId);
        SupportTicketReply reply = new SupportTicketReply();
        reply.setTicketId(ticketId);
        reply.setAuthorType(AUTHOR_USER);
        reply.setAuthorId(userId);
        reply.setAuthorName(user != null ? user.getUsername() : "用户#" + userId);
        reply.setContent(dto.getContent().trim());
        reply.setIsInternal(0);
        replyRepository.insert(reply);

        if (STATUS_RESOLVED.equals(ticket.getStatus())) {
            ticket.setStatus(STATUS_IN_PROGRESS);
            ticketRepository.updateById(ticket);
        }
    }

    // ─── 管理端 ───────────────────────────────────────────────

    public Page<TicketVO> adminList(String status, String category, String keyword, int page, int size) {
        LambdaQueryWrapper<SupportTicket> q = new LambdaQueryWrapper<SupportTicket>()
                .orderByDesc(SupportTicket::getCreateTime);
        if (StringUtils.hasText(status)) {
            q.eq(SupportTicket::getStatus, status.toUpperCase());
        }
        if (StringUtils.hasText(category)) {
            q.eq(SupportTicket::getCategory, category.toUpperCase());
        }
        if (StringUtils.hasText(keyword)) {
            q.and(w -> w.like(SupportTicket::getTitle, keyword)
                    .or().like(SupportTicket::getTicketNo, keyword));
        }
        Page<SupportTicket> p = ticketRepository.selectPage(new Page<>(page, size), q);

        Set<Long> userIds = p.getRecords().stream().map(SupportTicket::getUserId).collect(Collectors.toSet());
        Map<Long, String> usernames = loadUsernames(userIds);

        Set<Long> opsIds = p.getRecords().stream()
                .filter(t -> t.getAssignedTo() != null)
                .map(SupportTicket::getAssignedTo)
                .collect(Collectors.toSet());
        Map<Long, String> opsNames = loadOpsNames(opsIds);

        Page<TicketVO> out = new Page<>(p.getCurrent(), p.getSize(), p.getTotal());
        out.setRecords(p.getRecords().stream()
                .map(t -> {
                    TicketVO vo = toVO(t, true);
                    vo.setUsername(usernames.getOrDefault(t.getUserId(), ""));
                    if (t.getAssignedTo() != null) {
                        vo.setAssignedToName(opsNames.getOrDefault(t.getAssignedTo(), ""));
                    }
                    return vo;
                })
                .collect(Collectors.toList()));
        return out;
    }

    public TicketVO adminGet(Long ticketId) {
        SupportTicket ticket = requireTicket(ticketId);
        TicketVO vo = toVO(ticket, true);
        AuthUser user = authUserRepository.selectById(ticket.getUserId());
        if (user != null) vo.setUsername(user.getUsername());
        if (ticket.getAssignedTo() != null) {
            OpsAccount ops = opsAccountRepository.selectById(ticket.getAssignedTo());
            if (ops != null) vo.setAssignedToName(ops.getUsername());
        }
        vo.setReplies(loadReplies(ticketId, true));
        return vo;
    }

    @Transactional
    public void adminUpdate(Long ticketId, AdminTicketUpdateDTO dto) {
        SupportTicket ticket = requireTicket(ticketId);
        if (StringUtils.hasText(dto.getStatus())) {
            String s = dto.getStatus().toUpperCase();
            if (!VALID_STATUSES.contains(s)) throw new BusinessException("无效状态");
            ticket.setStatus(s);
            if (STATUS_RESOLVED.equals(s) && ticket.getResolvedAt() == null) {
                ticket.setResolvedAt(LocalDateTime.now());
            }
        }
        if (StringUtils.hasText(dto.getPriority())) {
            String pr = dto.getPriority().toUpperCase();
            if (!VALID_PRIORITIES.contains(pr)) throw new BusinessException("无效优先级");
            ticket.setPriority(pr);
        }
        if (dto.getAssignedTo() != null) {
            ticket.setAssignedTo(dto.getAssignedTo());
        }
        if (dto.getCloseReason() != null) {
            ticket.setCloseReason(dto.getCloseReason().trim());
        }
        ticketRepository.updateById(ticket);
    }

    @Transactional
    public void adminReply(Long ticketId, TicketReplyDTO dto) {
        SupportTicket ticket = requireTicket(ticketId);
        if (STATUS_CLOSED.equals(ticket.getStatus())) {
            throw new BusinessException("工单已关闭");
        }
        Long opsId = ThreadLocalUtil.getUserId();
        String opsName = "运营";
        if (opsId != null) {
            OpsAccount ops = opsAccountRepository.selectById(opsId);
            if (ops != null) opsName = ops.getUsername();
        }
        boolean internal = dto.getInternal() != null && dto.getInternal();
        SupportTicketReply reply = new SupportTicketReply();
        reply.setTicketId(ticketId);
        reply.setAuthorType(AUTHOR_OPS);
        reply.setAuthorId(opsId != null ? opsId : 0L);
        reply.setAuthorName(opsName);
        reply.setContent(dto.getContent().trim());
        reply.setIsInternal(internal ? 1 : 0);
        replyRepository.insert(reply);

        if (!internal && STATUS_OPEN.equals(ticket.getStatus())) {
            ticket.setStatus(STATUS_IN_PROGRESS);
            ticketRepository.updateById(ticket);
        }
    }

    @Transactional
    public void adminClose(Long ticketId, String reason) {
        SupportTicket ticket = requireTicket(ticketId);
        if (STATUS_CLOSED.equals(ticket.getStatus())) {
            throw new BusinessException("工单已经是关闭状态");
        }
        ticket.setStatus(STATUS_CLOSED);
        ticket.setCloseReason(StringUtils.hasText(reason) ? reason.trim() : "管理员关闭");
        ticketRepository.updateById(ticket);
    }

    public long countOpen() {
        Long c = ticketRepository.selectCount(
                new LambdaQueryWrapper<SupportTicket>()
                        .in(SupportTicket::getStatus, STATUS_OPEN, STATUS_IN_PROGRESS));
        return c == null ? 0L : c;
    }

    // ─── 私有工具 ─────────────────────────────────────────────

    private SupportTicket requireTicket(Long id) {
        SupportTicket t = ticketRepository.selectById(id);
        if (t == null || (t.getDeleted() != null && t.getDeleted() != 0)) {
            throw new ResourceNotFoundException("工单不存在");
        }
        return t;
    }

    private Long requireUserId() {
        Long uid = ThreadLocalUtil.getUserId();
        if (uid == null || uid <= 0) throw new BusinessException("请先登录");
        return uid;
    }

    private List<TicketReplyVO> loadReplies(Long ticketId, boolean includeInternal) {
        LambdaQueryWrapper<SupportTicketReply> q = new LambdaQueryWrapper<SupportTicketReply>()
                .eq(SupportTicketReply::getTicketId, ticketId)
                .orderByAsc(SupportTicketReply::getCreateTime);
        if (!includeInternal) {
            q.eq(SupportTicketReply::getIsInternal, 0);
        }
        return replyRepository.selectList(q).stream().map(r -> {
            TicketReplyVO vo = new TicketReplyVO();
            vo.setId(r.getId());
            vo.setTicketId(r.getTicketId());
            vo.setAuthorType(r.getAuthorType());
            vo.setAuthorId(r.getAuthorId());
            vo.setAuthorName(r.getAuthorName());
            vo.setContent(r.getContent());
            vo.setInternal(r.getIsInternal() != null && r.getIsInternal() == 1);
            vo.setCreateTime(r.getCreateTime());
            return vo;
        }).collect(Collectors.toList());
    }

    private Map<Long, String> loadUsernames(Set<Long> ids) {
        if (ids == null || ids.isEmpty()) return Map.of();
        return authUserRepository
                .selectList(new LambdaQueryWrapper<AuthUser>().in(AuthUser::getId, ids))
                .stream()
                .collect(Collectors.toMap(AuthUser::getId, u -> u.getUsername() == null ? "" : u.getUsername()));
    }

    private Map<Long, String> loadOpsNames(Set<Long> ids) {
        if (ids == null || ids.isEmpty()) return Map.of();
        return opsAccountRepository
                .selectList(new LambdaQueryWrapper<OpsAccount>().in(OpsAccount::getId, ids))
                .stream()
                .collect(Collectors.toMap(OpsAccount::getId, o -> o.getUsername() == null ? "" : o.getUsername()));
    }

    private TicketVO toVO(SupportTicket t, boolean includeContent) {
        TicketVO vo = new TicketVO();
        vo.setId(t.getId());
        vo.setTicketNo(t.getTicketNo());
        vo.setUserId(t.getUserId());
        vo.setCategory(t.getCategory());
        vo.setTitle(t.getTitle());
        if (includeContent) vo.setContent(t.getContent());
        vo.setStatus(t.getStatus());
        vo.setPriority(t.getPriority());
        vo.setAssignedTo(t.getAssignedTo());
        vo.setCloseReason(t.getCloseReason());
        vo.setResolvedAt(t.getResolvedAt());
        vo.setCreateTime(t.getCreateTime());
        vo.setUpdateTime(t.getUpdateTime());
        return vo;
    }

    private static String generateTicketNo() {
        String date = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd"));
        long seq = SEQ.incrementAndGet() % 100000;
        return String.format("TK%s%05d", date, seq);
    }
}
