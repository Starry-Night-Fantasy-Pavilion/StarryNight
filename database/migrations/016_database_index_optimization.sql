-- 数据库索引优化迁移文件
-- 用于提升查询性能，优化常用查询场景
-- 创建时间: 2024年
-- 注意：此文件使用简单的 ALTER TABLE 语句，如果索引已存在会报错但会被安装程序忽略

-- ============================================
-- 1. AI智能体相关索引优化
-- ============================================

-- AI智能体表：优化用户和公开状态查询
ALTER TABLE `__PREFIX__ai_agents` 
ADD INDEX `idx_user_public` (`user_id`, `is_public`);

-- AI智能体市场表：优化市场展示查询
ALTER TABLE `__PREFIX__ai_agent_market` 
ADD INDEX `idx_status_created` (`status`, `created_at` DESC),
ADD INDEX `idx_category_rating` (`category`, `rating` DESC);

-- AI智能体购买记录：优化用户购买查询
ALTER TABLE `__PREFIX__ai_agent_purchases` 
ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC);

-- AI智能体评论：优化评论查询
ALTER TABLE `__PREFIX__ai_agent_reviews` 
ADD INDEX `idx_agent_rating` (`agent_id`, `rating` DESC),
ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC);

-- ============================================
-- 2. 一致性检查系统索引优化
-- ============================================

-- 一致性报告表：优化项目和模块查询
ALTER TABLE `__PREFIX__consistency_reports` 
ADD INDEX `idx_project_module` (`project_id`, `module_type`),
ADD INDEX `idx_status_created` (`status`, `created_at` DESC);

-- 一致性冲突表：优化冲突查询
ALTER TABLE `__PREFIX__consistency_conflicts` 
ADD INDEX `idx_report_status` (`report_id`, `status`),
ADD INDEX `idx_type_severity` (`conflict_type`, `severity`);

-- ============================================
-- 3. 用户反馈系统索引优化
-- ============================================

-- 用户反馈表：优化状态和类型查询
ALTER TABLE `__PREFIX__user_feedback` 
ADD INDEX `idx_status_type` (`status`, `type`),
ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC),
ADD INDEX `idx_priority_status` (`priority`, `status`);

-- ============================================
-- 4. AI音乐创作模块索引优化
-- ============================================

-- AI音乐项目表：优化用户和状态查询
ALTER TABLE `__PREFIX__ai_music_project` 
ADD INDEX `idx_user_status` (`user_id`, `status`),
ADD INDEX `idx_genre_created` (`genre`, `created_at` DESC);

-- AI音乐旋律表：优化项目查询
ALTER TABLE `__PREFIX__ai_music_melody` 
ADD INDEX `idx_project_tempo` (`project_id`, `tempo`),
ADD INDEX `idx_key_signature` (`key_signature`);

-- AI音乐编曲表：优化项目和风格查询
ALTER TABLE `__PREFIX__ai_music_arrangement` 
ADD INDEX `idx_project_style` (`project_id`, `style`),
ADD INDEX `idx_density` (`density`);

-- AI音乐音轨表：优化项目和类型查询
ALTER TABLE `__PREFIX__ai_music_track` 
ADD INDEX `idx_project_type` (`project_id`, `type`),
ADD INDEX `idx_instrument` (`instrument`);

-- ============================================
-- 5. 动漫制作模块索引优化
-- ============================================

-- 动漫项目表：优化用户和状态查询
ALTER TABLE `__PREFIX__anime_projects` 
ADD INDEX `idx_user_status` (`user_id`, `status`),
ADD INDEX `idx_genre_created` (`genre`, `created_at` DESC);

-- 动漫分集剧本表：优化项目和集数查询
ALTER TABLE `__PREFIX__anime_episode_scripts` 
ADD INDEX `idx_project_episode` (`project_id`, `episode_no`),
ADD INDEX `idx_status` (`status`);

-- 动漫分镜表：优化场景查询
ALTER TABLE `__PREFIX__anime_storyboards` 
ADD INDEX `idx_scene_order` (`scene_id`, `order_index`);

-- 动漫动画表：优化项目和状态查询
ALTER TABLE `__PREFIX__anime_animations` 
ADD INDEX `idx_project_status` (`project_id`, `status`),
ADD INDEX `idx_type_created` (`animation_type`, `created_at` DESC);

-- 动漫视频合成表：优化项目和状态查询
ALTER TABLE `__PREFIX__anime_video_compositions` 
ADD INDEX `idx_project_status` (`project_id`, `status`),
ADD INDEX `idx_episode_created` (`episode_number`, `created_at` DESC);

-- ============================================
-- 6. 小说创作模块索引优化
-- ============================================

-- 小说表：优化用户和状态查询
ALTER TABLE `__PREFIX__novels` 
ADD INDEX `idx_user_status` (`user_id`, `status`),
ADD INDEX `idx_category_views` (`category_id`, `view_count` DESC),
ADD INDEX `idx_created_views` (`created_at` DESC, `view_count` DESC);

-- 小说章节表：优化小说和排序查询
ALTER TABLE `__PREFIX__novel_chapters` 
ADD INDEX `idx_novel_order` (`novel_id`, `chapter_order`),
ADD INDEX `idx_status_created` (`status`, `created_at` DESC);

-- ============================================
-- 7. 社区模块索引优化
-- ============================================

-- 社区分类表：优化排序和状态查询
ALTER TABLE `__PREFIX__community_categories` 
ADD INDEX `idx_sort_active` (`sort`, `is_active`);

-- 社区内容表：优化分类和状态查询
ALTER TABLE `__PREFIX__community_contents`
ADD INDEX `idx_category_status` (`category_id`, `status`),
ADD INDEX `idx_user_created` (`user_id`, `created_at` DESC),
ADD INDEX `idx_pinned_created` (`is_pinned`, `created_at` DESC);

-- ============================================
-- 8. 会员系统索引优化
-- ============================================

-- 会员等级表：优化排序查询
ALTER TABLE `__PREFIX__membership_levels` 
ADD INDEX `idx_sort_order` (`sort_order`);

-- 用户会员记录表：优化用户和状态查询
ALTER TABLE `__PREFIX__user_memberships` 
ADD INDEX `idx_user_status` (`user_id`, `status`),
ADD INDEX `idx_expires_at` (`expires_at`);

-- ============================================
-- 9. 通知和公告系统索引优化
-- ============================================

-- 通知栏表：优化状态和排序查询
ALTER TABLE `__PREFIX__notice_bar` 
ADD INDEX `idx_status_sort` (`status`, `sort_order`),
ADD INDEX `idx_start_end` (`start_time`, `end_time`);

-- 公告表：优化分类和状态查询
ALTER TABLE `__PREFIX__announcements` 
ADD INDEX `idx_category_status` (`category_id`, `status`),
ADD INDEX `idx_published_at` (`published_at` DESC);

-- 用户公告阅读记录表：优化用户和公告查询
ALTER TABLE `__PREFIX__user_announcement_reads` 
ADD INDEX `idx_user_announcement` (`user_id`, `announcement_id`),
ADD INDEX `idx_read_at` (`read_at` DESC);

-- ============================================
-- 10. 日志和统计表索引优化
-- ============================================

-- AI通道调用日志表：优化通道和时间查询
ALTER TABLE `__PREFIX__ai_channel_call_logs`
ADD INDEX `idx_channel_created` (`channel_id`, `created_at` DESC),
ADD INDEX `idx_status_created` (`success`, `created_at` DESC);

-- ============================================
-- 索引优化完成
-- ============================================
-- 注意：如果索引已存在，MySQL会报错但安装程序会忽略这些错误（错误代码 42000/1091）
