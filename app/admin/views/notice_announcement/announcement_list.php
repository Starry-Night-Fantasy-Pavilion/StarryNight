<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <h2 style="margin:0;">公告管理</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px;">
                <select name="category_id" class="form-control" style="width:140px;" onchange="this.form.submit()">
                    <option value="">全部分类</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($_GET['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-control" style="width:100px;" onchange="this.form.submit()">
                    <option value="">全部状态</option>
                    <option value="enabled" <?= ($_GET['status'] ?? '') === 'enabled' ? 'selected' : '' ?>>启用</option>
                    <option value="disabled" <?= ($_GET['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>禁用</option>
                </select>
            </form>
            <a href="/<?= $adminPrefix ?>/announcement/edit/new" class="btn btn-primary">发布公告</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>标题</th>
                        <th>分类</th>
                        <th>发布时间</th>
                        <th>置顶</th>
                        <th>弹窗</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="8" class="text-center text-muted">暂无公告</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                                <td><?= !empty($row['publish_at']) ? date('Y-m-d H:i', strtotime($row['publish_at'])) : '立即' ?></td>
                                <td><?= !empty($row['is_pinned']) ? '是' : '否' ?></td>
                                <td><?= !empty($row['is_popup']) ? '是' : '否' ?></td>
                                <td><?= ($row['status'] ?? '') === 'enabled' ? '启用' : '禁用' ?></td>
                                <td>
                                    <a href="/<?= $adminPrefix ?>/announcement/edit/<?= (int)$row['id'] ?>">编辑</a>
                                    <a href="/<?= $adminPrefix ?>/announcement/toggle/<?= (int)$row['id'] ?>"><?= ($row['status'] ?? '') === 'enabled' ? '禁用' : '启用' ?></a>
                                    <a href="/<?= $adminPrefix ?>/announcement/delete/<?= (int)$row['id'] ?>" onclick="return confirm('确定删除？');">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
