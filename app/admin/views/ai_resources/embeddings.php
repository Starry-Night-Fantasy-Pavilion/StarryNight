<?php
/** @var array $items */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">嵌入式模型管理</div>
    </div>
    <div class="dashboard-card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>名称</label>
                    <input class="form-control" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:3;">
                    <label>描述</label>
                    <input class="form-control" name="description" value="<?= htmlspecialchars($edit['description'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>类型</label>
                    <input class="form-control" name="type" value="<?= htmlspecialchars($edit['type'] ?? 'openai') ?>">
                </div>
                <div class="form-group" style="flex:2;">
                    <label>Base URL</label>
                    <input class="form-control" name="base_url" placeholder="可选" value="<?= htmlspecialchars($edit['base_url'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:2;">
                    <label>API Key</label>
                    <input class="form-control" name="api_key" placeholder="可选" value="<?= htmlspecialchars($edit['api_key'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label>配置(JSON)</label>
                    <textarea class="form-control" name="config_json" rows="3" placeholder='{"dimension":1536}'><?= htmlspecialchars($edit['config_json'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label style="display:flex; gap:10px; align-items:center;">
                        <input type="checkbox" name="is_enabled" value="1" <?= ((int)($edit['is_enabled'] ?? 1) === 1) ? 'checked' : '' ?>> 启用
                    </label>
                </div>
                <div class="form-group">
                    <label style="display:flex; gap:10px; align-items:center;">
                        <input type="checkbox" name="is_user_customizable" value="1" <?= ((int)($edit['is_user_customizable'] ?? 0) === 1) ? 'checked' : '' ?>> 允许用户自定义
                    </label>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit"><?= !empty($edit) ? '保存修改' : '新增/覆盖' ?></button>
                    <?php if (!empty($edit)): ?>
                        <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/embeddings" style="margin-left:10px;">取消编辑</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-card" style="margin-top:16px;">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">列表</div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>类型</th>
                    <th>启用</th>
                    <th>允许用户自定义</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['type'] ?? '') ?></td>
                        <td><?= ((int)($it['is_enabled'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td><?= ((int)($it['is_user_customizable'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/embeddings?edit=<?= (int)$it['id'] ?>">编辑</a>
                            <form method="POST" action="" onsubmit="return confirm('确定删除该嵌入式模型？');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6">暂无配置</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

