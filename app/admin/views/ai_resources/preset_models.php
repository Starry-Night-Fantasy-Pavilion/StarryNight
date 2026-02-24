<?php
/** @var array $items */
/** @var array $channels */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">平台预设模型名称</div>
    </div>
    <div class="dashboard-card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>模型名称</label>
                    <input class="form-control" name="name" placeholder="例如 gpt-4o-mini" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:3;">
                    <label>描述</label>
                    <input class="form-control" name="description" placeholder="可选" value="<?= htmlspecialchars($edit['description'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>默认渠道</label>
                    <select class="form-control" name="default_channel_id">
                        <option value="0">不指定</option>
                        <?php foreach ($channels as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($edit['default_channel_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex; gap:10px; align-items:center;">
                        <input type="checkbox" name="is_enabled" value="1" <?= ((int)($edit['is_enabled'] ?? 1) === 1) ? 'checked' : '' ?>> 启用
                    </label>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit"><?= !empty($edit) ? '保存修改' : '新增/覆盖' ?></button>
                    <?php if (!empty($edit)): ?>
                        <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/preset-models" style="margin-left:10px;">取消编辑</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-card" style="margin-top: 16px;">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">模型列表</div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>模型</th>
                    <th>描述</th>
                    <th>默认渠道</th>
                    <th>启用</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['description'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['channel_name'] ?? '') ?></td>
                        <td><?= ((int)($it['is_enabled'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/preset-models?edit=<?= (int)$it['id'] ?>">编辑</a>
                            <form method="POST" action="" onsubmit="return confirm('确定删除该模型？');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6">暂无预设模型</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

