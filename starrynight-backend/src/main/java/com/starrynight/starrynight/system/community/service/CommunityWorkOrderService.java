package com.starrynight.starrynight.system.community.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.community.dto.CommunityWorkOrderDTO;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;

import java.sql.Timestamp;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class CommunityWorkOrderService {

    private static final String UNION_SQL =
            "SELECT kind, target_id, post_id, comment_id, user_id, title_snippet, content_preview, reason_note, create_time FROM ("
                    + "SELECT 'POST' AS kind, id AS target_id, id AS post_id, CAST(NULL AS UNSIGNED) AS comment_id, user_id, "
                    + "COALESCE(title,'') AS title_snippet, LEFT(content, 200) AS content_preview, "
                    + "COALESCE(reject_reason,'') AS reason_note, create_time "
                    + "FROM community_post WHERE audit_status = 0 AND deleted = 0 "
                    + "UNION ALL "
                    + "SELECT 'COMMENT', id, post_id, id, user_id, '', LEFT(content, 200), COALESCE(moderation_note,''), create_time "
                    + "FROM community_comment WHERE audit_status = 0 AND deleted = 0"
                    + ") t ORDER BY create_time DESC LIMIT ? OFFSET ?";

    @Autowired
    private JdbcTemplate jdbcTemplate;

    @Autowired
    private AuthUserRepository authUserRepository;

    public long countPending() {
        Long totalObj = jdbcTemplate.queryForObject(
                "SELECT (SELECT COUNT(*) FROM community_post WHERE audit_status = 0 AND deleted = 0) + "
                        + "(SELECT COUNT(*) FROM community_comment WHERE audit_status = 0 AND deleted = 0)",
                Long.class);
        return totalObj == null ? 0L : totalObj;
    }

    public Page<CommunityWorkOrderDTO> list(int page, int size) {
        Long totalObj = jdbcTemplate.queryForObject(
                "SELECT (SELECT COUNT(*) FROM community_post WHERE audit_status = 0 AND deleted = 0) + "
                        + "(SELECT COUNT(*) FROM community_comment WHERE audit_status = 0 AND deleted = 0)",
                Long.class);
        long total = totalObj == null ? 0L : totalObj;
        int offset = Math.max(0, (page - 1) * size);
        List<CommunityWorkOrderDTO> records = jdbcTemplate.query(UNION_SQL, (rs, rowNum) -> {
            CommunityWorkOrderDTO d = new CommunityWorkOrderDTO();
            d.setKind(rs.getString("kind"));
            d.setTargetId(rs.getLong("target_id"));
            d.setPostId(rs.getLong("post_id"));
            long cid = rs.getLong("comment_id");
            d.setCommentId(rs.wasNull() ? null : cid);
            d.setUserId(rs.getLong("user_id"));
            d.setTitleSnippet(rs.getString("title_snippet"));
            d.setContentPreview(rs.getString("content_preview"));
            d.setReasonNote(rs.getString("reason_note"));
            Timestamp ts = rs.getTimestamp("create_time");
            d.setCreateTime(ts == null ? null : ts.toLocalDateTime());
            return d;
        }, size, offset);
        Set<Long> uids = records.stream().map(CommunityWorkOrderDTO::getUserId).collect(Collectors.toSet());
        Map<Long, String> names = loadUsernames(uids);
        for (CommunityWorkOrderDTO d : records) {
            d.setUsername(names.getOrDefault(d.getUserId(), ""));
        }
        Page<CommunityWorkOrderDTO> out = new Page<>(page, size, total);
        out.setRecords(records);
        return out;
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
}
