<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '');

// 视图内简单工具函数，避免使用 $this 导致 500
if (!function_exists('consistency_truncate_text')) {
    function consistency_truncate_text(string $text, int $length = 150): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
}

if (!function_exists('consistency_get_setting_type_label')) {
    function consistency_get_setting_type_label(string $type): string
    {
        $map = [
            'worldview' => '世界�?,
            'character' => '角色',
            'event'     => '事件',
            'rule'      => '规则',
            'other'     => '其他',
        ];
        return $map[$type] ?? $type;
    }
}
?>

<link rel="stylesheet" href="/static/frontend/views/css/consistency-core-settings.css?v=<?= time() ?>">

<div class="consistency-check-container" data-admin-prefix="<?= htmlspecialchars(trim((string)$adminPrefix, '/'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="page-header">
        <h1 class="page-title">核心设定管理</h1>
        <p class="page-description">管理项目的核心设定，包括世界观、角色、事件和规则�?/p>
    </div>

    <div class="consistency-nav-tabs">
        <ul class="nav-tabs">
            <li class="nav-tab <?= ($currentPage === 'consistency-config') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/config">系统配置</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-core-settings') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/core-settings">核心设定</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-check') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/check">一致性检�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-reports') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/reports">检查报�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-analytics') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/analytics">分析统计</a>
            </li>
        </ul>
    </div>

    <div class="core-settings-content">
        <div class="actions-bar">
            <div class="actions-left">
                <button class="btn btn-primary" type="button" data-action="open-add-modal">
                    <?= icon('plus') ?> 添加核心设定
                </button>
                <button class="btn btn-secondary" type="button" data-action="open-import-modal">
                    <?= icon('upload') ?> 批量导入
                </button>
                <button class="btn btn-outline" type="button" data-action="export-settings">
                    <?= icon('download') ?> 导出设定
                </button>
            </div>
            <div class="actions-right">
                <div class="filter-group">
                    <select id="typeFilter" class="form-select">
                        <option value="">所有类�?/option>
                        <option value="worldview">世界�?/option>
                        <option value="character">角色</option>
                        <option value="event">事件</option>
                        <option value="rule">规则</option>
                        <option value="other">其他</option>
                    </select>
                    <input type="text" id="searchInput" class="form-input" placeholder="搜索核心设定...">
                </div>
            </div>
        </div>

        <div class="settings-grid" id="settingsGrid">
            <?php foreach ($coreSettings as $setting): ?>
            <div class="setting-card" data-type="<?= $setting['setting_type'] ?>" data-title="<?= strtolower($setting['title']) ?>">
                <div class="setting-header">
                    <div class="setting-type type-<?= $setting['setting_type'] ?>">
                        <?= consistency_get_setting_type_label($setting['setting_type']) ?>
                    </div>
                    <div class="setting-actions">
                        <button class="btn-icon" type="button" data-action="edit-setting" data-id="<?= (int)$setting['id'] ?>" title="编辑">
                            <?= icon('edit') ?>
                        </button>
                        <button class="btn-icon" type="button" data-action="delete-setting" data-id="<?= (int)$setting['id'] ?>" title="删除">
                            <?= icon('trash') ?>
                        </button>
                    </div>
                </div>
                <div class="setting-content">
                    <h3 class="setting-title"><?= htmlspecialchars($setting['title']) ?></h3>
                    <div class="setting-description">
                        <?= consistency_truncate_text(htmlspecialchars($setting['content']), 150) ?>
                    </div>
                    <div class="setting-meta">
                        <span class="meta-item">
                            <?= icon('calendar') ?> <?= date('Y-m-d', strtotime($setting['created_at'])) ?>
                        </span>
                        <span class="meta-item">
                            <?= icon('check-circle') ?> <?= $setting['is_active'] ? '已激�? : '未激�? ?>
                        </span>
                        <?php if ($setting['vector_id']): ?>
                        <span class="meta-item">
                            <?= icon('database') ?> 已向量化
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($coreSettings)): ?>
        <div class="empty-state">
            <div class="empty-icon"><?= icon('inbox') ?></div>
            <h3>暂无核心设定</h3>
            <p>点击"添加核心设定"按钮开始创建项目的核心设定</p>
            <button class="btn btn-primary" type="button" data-action="open-add-modal">添加第一个核心设�?/button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 添加/编辑核心设定模态框 -->
<div id="settingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">添加核心设定</h3>
            <button class="modal-close" type="button" data-action="close-setting-modal">&times;</button>
        </div>
        <form id="settingForm" method="POST">
            <input type="hidden" id="settingId" name="id">
            <div class="form-group">
                <label for="settingType" class="form-label">设定类型</label>
                <select id="settingType" name="setting_type" class="form-select" required>
                    <option value="worldview">世界�?/option>
                    <option value="character">角色</option>
                    <option value="event">事件</option>
                    <option value="rule">规则</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div class="form-group">
                <label for="settingTitle" class="form-label">标题</label>
                <input type="text" id="settingTitle" name="title" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="settingContent" class="form-label">内容</label>
                <textarea id="settingContent" name="content" class="form-textarea" rows="8" required></textarea>
            </div>
            <div class="form-group">
                <label for="settingMetadata" class="form-label">元数�?(JSON格式)</label>
                <textarea id="settingMetadata" name="metadata" class="form-textarea" rows="4" placeholder='{"key": "value"}'></textarea>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="settingActive" name="is_active" checked>
                    <span class="checkbox-text">激活此设定</span>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">保存</button>
                <button type="button" class="btn btn-outline" data-action="close-setting-modal">取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 批量导入模态框 -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>批量导入核心设定</h3>
            <button class="modal-close" type="button" data-action="close-import-modal">&times;</button>
        </div>
        <form id="importForm" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="importFile" class="form-label">选择文件</label>
                <input type="file" id="importFile" name="import_file" class="form-input" accept=".csv,.json,.xlsx" required>
                <p class="form-help">支持CSV、JSON、Excel格式文件</p>
            </div>
            <div class="form-group">
                <label for="importMapping" class="form-label">字段映射</label>
                <div class="mapping-grid">
                    <div class="mapping-item">
                        <label>标题字段:</label>
                        <select name="mapping[title]" class="form-select">
                            <option value="title">title</option>
                            <option value="name">name</option>
                            <option value="名称">名称</option>
                        </select>
                    </div>
                    <div class="mapping-item">
                        <label>内容字段:</label>
                        <select name="mapping[content]" class="form-select">
                            <option value="content">content</option>
                            <option value="description">description</option>
                            <option value="描述">描述</option>
                        </select>
                    </div>
                    <div class="mapping-item">
                        <label>类型字段:</label>
                        <select name="mapping[type]" class="form-select">
                            <option value="type">type</option>
                            <option value="category">category</option>
                            <option value="类型">类型</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">导入</button>
                <button type="button" class="btn btn-outline" data-action="close-import-modal">取消</button>
            </div>
        </form>
    </div>
</div>

<script src="/static/frontend/views/js/consistency-core-settings.js?v=<?= time() ?>"></script>
