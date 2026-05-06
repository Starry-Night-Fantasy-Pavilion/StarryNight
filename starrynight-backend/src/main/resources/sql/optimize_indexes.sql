-- StarryNight Database Index Optimization
-- Run this script to add performance indexes

-- Novel Indexes
CREATE INDEX IF NOT EXISTS idx_novel_user_id ON t_novel(user_id);
CREATE INDEX IF NOT EXISTS idx_novel_status ON t_novel(status);
CREATE INDEX IF NOT EXISTS idx_novel_update_time ON t_novel(update_time);

-- Chapter Indexes
CREATE INDEX IF NOT EXISTS idx_chapter_novel_id ON t_chapter(novel_id);
CREATE INDEX IF NOT EXISTS idx_chapter_order ON t_chapter(chapter_order);
CREATE INDEX IF NOT EXISTS idx_chapter_status ON t_chapter(status);

-- Character Indexes
CREATE INDEX IF NOT EXISTS idx_character_novel_id ON t_novel_character(novel_id);
CREATE INDEX IF NOT EXISTS idx_character_update_time ON t_novel_character(update_time);

-- Knowledge Base Indexes
CREATE INDEX IF NOT EXISTS idx_knowledge_user_id ON t_knowledge_base(user_id);
CREATE INDEX IF NOT EXISTS idx_knowledge_type ON t_knowledge_base(type);

-- Knowledge Chunk Indexes
CREATE INDEX IF NOT EXISTS idx_chunk_knowledge_id ON t_knowledge_chunk(knowledge_id);
CREATE INDEX IF NOT EXISTS idx_chunk_content ON t_knowledge_chunk(content(255));

-- Material Item Indexes
CREATE INDEX IF NOT EXISTS idx_material_user_id ON t_material_item(user_id);
CREATE INDEX IF NOT EXISTS idx_material_type ON t_material_item(type);
CREATE INDEX IF NOT EXISTS idx_material_novel_id ON t_material_item(novel_id);
CREATE INDEX IF NOT EXISTS idx_material_update_time ON t_material_item(update_time);

-- Prompt Template Indexes
CREATE INDEX IF NOT EXISTS idx_prompt_user_id ON t_prompt_template(user_id);
CREATE INDEX IF NOT EXISTS idx_prompt_category ON t_prompt_template(category);
CREATE INDEX IF NOT EXISTS idx_prompt_builtin ON t_prompt_template(is_builtin);

-- Operation Log Indexes
CREATE INDEX IF NOT EXISTS idx_log_user_id ON t_operation_log(user_id);
CREATE INDEX IF NOT EXISTS idx_log_create_time ON t_operation_log(create_time);
CREATE INDEX IF NOT EXISTS idx_log_type ON t_operation_log(operation_type);

-- User Session Indexes
CREATE INDEX IF NOT EXISTS idx_session_user_id ON t_user_session(user_id);
CREATE INDEX IF NOT EXISTS idx_session_token ON t_user_session(token);

-- Notification Indexes
CREATE INDEX IF NOT EXISTS idx_notification_user_id ON t_notification(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_read ON t_notification(is_read);
CREATE INDEX IF NOT EXISTS idx_notification_create_time ON t_notification(create_time);