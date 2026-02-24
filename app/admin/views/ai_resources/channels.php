<?php
/** @var array $channels */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<?php
$adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
$group = trim((string)($_GET['group'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));

// 生成分组选项（用于 chips）
$groups = [];
foreach ($channels as $c) {
    $g = trim((string)($c['model_group'] ?? ''));
    if ($g !== '') {
        $groups[$g] = true;
    }
}
$groups = array_keys($groups);
sort($groups, SORT_STRING);

$openModal = isset($_GET['new']) || !empty($edit);
?>

<div class="ai-page">
    <div class="dashboard-card ai-header">
        <div class="dashboard-card-body">
            <div class="ai-toolbar">
                <h2 class="ai-title">AI 配置（URL / Key / 模型名）</h2>
                <div class="ai-actions">
                    <form method="GET" action="" style="display:flex; gap:10px; align-items:center; margin:0;">
                        <input type="hidden" name="group" value="<?= htmlspecialchars($group) ?>">
                        <input class="form-control" name="q" placeholder="搜索 URL / 分组 / 模型名" value="<?= htmlspecialchars($q) ?>" style="width:260px;">
                        <button class="btn btn-secondary" type="submit">搜索</button>
                    </form>
                    <a class="btn btn-primary" href="/<?= $adminPrefix ?>/ai/channels?new=1">新增配置</a>
                </div>
            </div>

            <?php if (!empty($groups)): ?>
                <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:nowrap; overflow:auto; padding-bottom:4px;">
                    <a class="ai-tab <?= $group === '' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/ai/channels<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">全部</a>
                    <?php foreach ($groups as $g): ?>
                        <?php
                        $href = "/{$adminPrefix}/ai/channels?group=" . rawurlencode($g);
                        if ($q !== '') { $href .= "&q=" . rawurlencode($q); }
                        ?>
                        <a class="ai-tab <?= $group === $g ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($g) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ai-list">
        <?php foreach ($channels as $c): ?>
            <?php
            $modelsText = (string)($c['models_text'] ?? '');
            $modelsCount = 0;
            if (trim($modelsText) !== '') {
                $lines = preg_split('/\R/u', $modelsText);
                $lines = array_values(array_filter(array_map('trim', $lines)));
                $modelsCount = count($lines);
            }
            $isEnabled = (($c['status'] ?? 'enabled') === 'enabled');
            ?>
            <div class="ai-item">
                <div class="ai-item-top">
                    <div class="ai-item-main">
                        <div class="ai-item-name">
                            #<?= (int)$c['id'] ?>
                            <?= htmlspecialchars(($c['name'] ?? '') !== '' ? $c['name'] : '配置') ?>
                            <?php if ($isEnabled): ?>
                                <span class="ai-badge ok" style="margin-left:8px;">启用</span>
                            <?php else: ?>
                                <span class="ai-badge off" style="margin-left:8px;">禁用</span>
                            <?php endif; ?>
                        </div>
                        <div class="ai-item-sub">
                            <?php if (trim((string)($c['model_group'] ?? '')) !== ''): ?>
                                <span class="ai-kv"><span style="opacity:.7;">分组</span><b><?= htmlspecialchars($c['model_group']) ?></b></span>
                            <?php endif; ?>
                            <span class="ai-kv ai-monospace"><span style="opacity:.7;">URL</span><b><?= htmlspecialchars($c['base_url'] ?? '') ?></b></span>
                            <span class="ai-kv ai-monospace"><span style="opacity:.7;">Key</span><b><?= htmlspecialchars(\app\models\AIChannel::maskKey($c['api_key'] ?? null)) ?></b></span>
                            <span class="ai-kv"><span style="opacity:.7;">模型</span><b><?= (int)$modelsCount ?></b></span>
                        </div>
                    </div>
                    <div class="ai-actions">
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/channels?edit=<?= (int)$c['id'] ?>">编辑</a>
                        <form method="POST" action="" onsubmit="return confirm('确定删除该配置？');" style="display:inline; margin:0;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-danger" type="submit">删除</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($channels)): ?>
            <div class="ai-item">暂无配置</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($openModal): ?>
    <?php
    $modalTitle = !empty($edit) ? ('编辑配置 #' . (int)$edit['id']) : '新增配置';
    $closeHref = "/{$adminPrefix}/ai/channels";
    if ($group !== '') { $closeHref .= "?group=" . rawurlencode($group); }
    if ($q !== '') {
        $closeHref .= (strpos($closeHref, '?') === false ? '?' : '&') . "q=" . rawurlencode($q);
    }
    ?>
    <div class="ai-modal-overlay">
        <div class="ai-modal">
            <div class="ai-modal-header">
                <div class="ai-modal-title"><?= htmlspecialchars($modalTitle) ?></div>
                <a class="ai-modal-close" href="<?= htmlspecialchars($closeHref) ?>">×</a>
            </div>
            <div class="ai-modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

                    <div class="form-row">
                        <div class="form-group" style="flex:2;">
                            <label>Base URL</label>
                            <input class="form-control ai-monospace" name="base_url" value="<?= htmlspecialchars($edit['base_url'] ?? '') ?>" required>
                        </div>
                        <div class="form-group" style="flex:2;">
                            <label>API Key</label>
                            <input class="form-control ai-monospace" name="api_key" value="<?= htmlspecialchars($edit['api_key'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex:1;">
                            <label>模型名称（每行一个）</label>
                            <textarea class="form-control ai-monospace" name="models_text" rows="8"><?= htmlspecialchars($edit['models_text'] ?? '') ?></textarea>
                            <div class="ai-help">示例：`gpt-4o-mini` / `claude-3-5-sonnet`（每行一个）</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>分组（可选）</label>
                            <input class="form-control" name="model_group" value="<?= htmlspecialchars($edit['model_group'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>状态</label>
                            <select class="form-control" name="status">
                                <option value="enabled" <?= (($edit['status'] ?? 'enabled') === 'enabled') ? 'selected' : '' ?>>启用</option>
                                <option value="disabled" <?= (($edit['status'] ?? '') === 'disabled') ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                    </div>

                    <details style="margin-top: 6px;">
                        <summary style="cursor:pointer; color: rgba(255,255,255,0.75);">高级选项</summary>
                        <div style="margin-top: 10px;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>名称（可选）</label>
                                    <input class="form-control" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>类型（可选）</label>
                                    <input class="form-control" name="type" value="<?= htmlspecialchars($edit['type'] ?? 'custom') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>优先级</label>
                                    <input class="form-control" type="number" name="priority" value="<?= htmlspecialchars((string)($edit['priority'] ?? 0)) ?>">
                                </div>
                                <div class="form-group">
                                    <label>权重</label>
                                    <input class="form-control" type="number" name="weight" value="<?= htmlspecialchars((string)($edit['weight'] ?? 100)) ?>">
                                </div>
                                <div class="form-group">
                                    <label>并发限制</label>
                                    <input class="form-control" type="number" name="concurrency_limit" value="<?= htmlspecialchars((string)($edit['concurrency_limit'] ?? 0)) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group" style="flex:1;">
                                    <label>额外配置(JSON)</label>
                                    <textarea class="form-control ai-monospace" name="config_json" rows="3"><?= htmlspecialchars($edit['config_json'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </details>

                    <div class="form-row" style="margin-top: 12px;">
                        <div class="form-group" style="display:flex; align-items:flex-end;">
                            <button class="btn btn-primary" type="submit">保存</button>
                            <a class="btn btn-secondary" href="<?= htmlspecialchars($closeHref) ?>" style="margin-left:10px;">取消</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

