<?php
/** @var array $items */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">通用智能体管理</div>
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
                <div class="form-group" style="flex:2;">
                    <label>角色</label>
                    <input class="form-control" name="role" placeholder="例如 写作助手/客服等" value="<?= htmlspecialchars($edit['role'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select class="form-control" name="status">
                        <option value="draft" <?= (($edit['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>草稿</option>
                        <option value="pending" <?= (($edit['status'] ?? '') === 'pending') ? 'selected' : '' ?>>待审核</option>
                        <option value="approved" <?= (($edit['status'] ?? '') === 'approved') ? 'selected' : '' ?>>已通过</option>
                        <option value="rejected" <?= (($edit['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>已拒绝</option>
                        <option value="disabled" <?= (($edit['status'] ?? '') === 'disabled') ? 'selected' : '' ?>>已禁用</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>定价(星夜币)</label>
                    <input class="form-control" name="price_coin" value="<?= htmlspecialchars((string)($edit['price_coin'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label style="display:flex; gap:10px; align-items:center;">
                        <input type="checkbox" name="is_public" value="1" <?= ((int)($edit['is_public'] ?? 0) === 1) ? 'checked' : '' ?>> 公开
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>能力(JSON)</label>
                    <textarea class="form-control" name="abilities_json" rows="3" placeholder='["summarize","rewrite"]'><?= htmlspecialchars($edit['abilities_json'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>可用模型(JSON数组)</label>
                    <textarea class="form-control" name="available_models_json" rows="2" placeholder='["gpt-4o-mini","claude-3-5-sonnet"]'><?= htmlspecialchars($edit['available_models_json'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label>提示词/系统指令</label>
                    <textarea class="form-control" name="prompt" rows="6"><?= htmlspecialchars($edit['prompt'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit"><?= !empty($edit) ? '保存修改' : '新增智能体' ?></button>
                    <?php if (!empty($edit)): ?>
                        <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/agents" style="margin-left:10px;">取消编辑</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="dashboard-card" style="margin-top:16px;">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">智能体列表</div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>角色</th>
                    <th>状态</th>
                    <th>公开</th>
                    <th>定价</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['role'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['status'] ?? '') ?></td>
                        <td><?= ((int)($it['is_public'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td><?= htmlspecialchars((string)($it['price_coin'] ?? '0')) ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/agents?edit=<?= (int)$it['id'] ?>">编辑</a>
                            <form method="POST" action="" onsubmit="return confirm('确定删除该智能体？');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7">暂无智能体</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

