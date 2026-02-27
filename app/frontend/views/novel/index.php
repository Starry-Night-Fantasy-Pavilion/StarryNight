<?php
/**
 * 我的小说列表视图（兼容旧路径）
 *
 * 说明：
 * NovelController::index() 会渲染 ../views/novel/index.php，
 * 这里直接复用新的项目列表视图 ../views/novel/project/index.php，
 * 保持「我的小说」界面风格一致。
 *
 * 依赖变量：
 * - $novels : array 小说列表
 */

require __DIR__ . '/project/index.php';

