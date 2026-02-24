<?php
/** @var array $items */
/** @var array|null $edit */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">通用提示词模板管理</div>
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
                    <label>所属功能</label>
                    <input class="form-control" name="feature" placeholder="chat/image/novel/..." value="<?= htmlspecialchars($edit['feature'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>分类</label>
                    <input class="form-control" name="category" value="<?= htmlspecialchars($edit['category'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>状态</label>
                    <select class="form-control" name="status">
                        <option value="draft" <?= (($edit['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>草稿</option>
                        <option value="pending" <?= (($edit['status'] ?? '') === 'pending') ? 'selected' : '' ?>>待审核</option>
                        <option value="approved" <?= (($edit['status'] ?? '') === 'approved') ? 'selected' : '' ?>>已通过</option>
                        <option value="rejected" <?= (($edit['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>已拒绝</option>
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
                <div class="form-group" style="flex: 1;">
                    <label>模板内容（填写则生成新版本）</label>
                    <textarea class="form-control" name="content" rows="6" placeholder="在这里填写提示词模板内容..."><?= htmlspecialchars($edit['current_content'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit"><?= !empty($edit) ? '保存（写入新版本）' : '新增模板' ?></button>
                    <?php if (!empty($edit)): ?>
                        <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                        <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/templates" style="margin-left:10px;">取消编辑</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <div style="color:#666; font-size:12px; margin-top:8px;">
            说明：为了简单起见，此页新增会创建模板；后续可扩展为“编辑/发布/版本回滚”。
        </div>
    </div>
</div>

<div class="dashboard-card" style="margin-top:16px;">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">模板列表</div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>功能/分类</th>
                    <th>状态</th>
                    <th>公开</th>
                    <th>定价</th>
                    <th>版本</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars(($it['feature'] ?? '') . '/' . ($it['category'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($it['status'] ?? '') ?></td>
                        <td><?= ((int)($it['is_public'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td><?= htmlspecialchars((string)($it['price_coin'] ?? '0')) ?></td>
                        <td><?= htmlspecialchars((string)($it['current_version'] ?? '-')) ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/templates?edit=<?= (int)$it['id'] ?>">编辑</a>
                            <form method="POST" action="" onsubmit="return confirm('确定删除该模板？');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                <button class="btn btn-danger" type="submit">删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8">暂无模板</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

