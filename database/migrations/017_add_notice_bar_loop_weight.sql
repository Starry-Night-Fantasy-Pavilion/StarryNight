-- 为通知栏增加循环权重字段，用于控制跑马灯重复次数

ALTER TABLE `__PREFIX__notice_bar`
  ADD COLUMN `loop_weight` INT(11) NULL DEFAULT NULL COMMENT '循环权重（为空则按优先级推算）' AFTER `priority`;

