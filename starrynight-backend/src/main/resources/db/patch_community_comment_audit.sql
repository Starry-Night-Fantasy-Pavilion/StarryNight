-- 评论审核状态；已有库执行一次；全新安装请同步 schema.sql / table.sql
ALTER TABLE community_comment
    ADD COLUMN audit_status TINYINT NOT NULL DEFAULT 1 COMMENT '0待审 1通过 2驳回' AFTER `content`,
    ADD COLUMN moderation_note VARCHAR(500) NULL COMMENT '审核说明/自动备注' AFTER `audit_status`;
