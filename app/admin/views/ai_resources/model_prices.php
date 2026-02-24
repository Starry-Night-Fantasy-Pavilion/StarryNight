<?php
/** @var array $items */
/** @var array $channels */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">模型价格管理</div>
    </div>
    <div class="dashboard-card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>所属渠道</label>
                    <select class="form-control" name="channel_id" required>
                        <option value="">请选择</option>
                        <?php foreach ($channels as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($edit['channel_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'] ?? '') ?> (<?= htmlspecialchars($c['type'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:2;">
                    <label>模型名称</label>
                    <input class="form-control" name="model_name" placeholder="例如 gpt-4o-mini" required value="<?= htmlspecialchars($edit['model_name'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>输入(星夜币/1K)</label>
                    <input class="form-control" name="input_coin_per_1k" value="<?= htmlspecialchars((string)($edit['input_coin_per_1k'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label>输出(星夜币/1K)</label>
                    <input class="form-control" name="output_coin_per_1k" value="<?= htmlspecialchars((string)($edit['output_coin_per_1k'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label>盈利百分比(0-100)</label>
                    <input class="form-control" name="profit_percent" value="<?= htmlspecialchars((string)($edit['profit_percent'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label style="display:flex; gap:10px; align-items:center;">
                        <input type="checkbox" name="is_active" value="1" <?= ((int)($edit['is_active'] ?? 1) === 1) ? 'checked' : '' ?>> 启用
                    </label>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit"><?= !empty($edit) ? '保存修改' : '新增/覆盖' ?></button>
                    <?php if (!empty($edit)): ?>
                        <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/model-prices" style="margin-left:10px;">取消编辑</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <div style="color:#666; font-size:12px; margin-top:8px;">
            说明：同一「渠道 + 模型名称」重复新增会覆盖更新（基于唯一键）。
        </div>
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
                    <th>渠道</th>
                    <th>模型</th>
                    <th>输入/1K</th>
                    <th>输出/1K</th>
                    <th>盈利%</th>
                    <th>启用</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars(($it['channel_name'] ?? '') . ' (' . ($it['channel_type'] ?? '') . ')') ?></td>
                        <td><?= htmlspecialchars($it['model_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars((string)($it['input_coin_per_1k'] ?? '0')) ?></td>
                        <td><?= htmlspecialchars((string)($it['output_coin_per_1k'] ?? '0')) ?></td>
                        <td><?= htmlspecialchars((string)($it['profit_percent'] ?? '0')) ?></td>
                        <td><?= ((int)($it['is_active'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/model-prices?edit=<?= (int)$it['id'] ?>">编辑</a>
                            <form method="POST" action="" onsubmit="return confirm('确定删除该定价？');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8">暂无模型价格</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

