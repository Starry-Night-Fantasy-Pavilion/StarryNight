-- 调整通知栏表结构以匹配最新代码（NoticeBar 模型）

ALTER TABLE `__PREFIX__notice_bar`
  ADD COLUMN `priority` INT(11) NOT NULL DEFAULT 0 COMMENT '优先级，越大越靠前' AFTER `link`;

ALTER TABLE `__PREFIX__notice_bar`
  ADD COLUMN `display_from` DATETIME NULL DEFAULT NULL COMMENT '显示开始时间' AFTER `priority`,
  ADD COLUMN `display_to` DATETIME NULL DEFAULT NULL COMMENT '显示结束时间' AFTER `display_from`,
  ADD COLUMN `lang` VARCHAR(20) NOT NULL DEFAULT 'zh-CN' COMMENT '语言代码' AFTER `status`;

ALTER TABLE `__PREFIX__notice_bar`
  MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'enabled' COMMENT 'enabled / disabled';

