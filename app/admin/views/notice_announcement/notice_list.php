<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <h2 style="margin:0;">通知栏管理</h2>
        <div style="display:flex; gap:8px;">
            <form method="GET" style="display:flex; gap:8px;">
                <select name="lang" class="form-control" style="width:100px;" onchange="this.form.submit()">
                    <option value="">全部语言</option>
                    <option value="zh-CN" <?= ($_GET['lang'] ?? '') === 'zh-CN' ? 'selected' : '' ?>>zh-CN</option>
                    <option value="en" <?= ($_GET['lang'] ?? '') === 'en' ? 'selected' : '' ?>>en</option>
                </select>
                <select name="status" class="form-control" style="width:100px;" onchange="this.form.submit()">
                    <option value="">全部状态</option>
                    <option value="enabled" <?= ($_GET['status'] ?? '') === 'enabled' ? 'selected' : '' ?>>启用</option>
                    <option value="disabled" <?= ($_GET['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>禁用</option>
                </select>
            </form>
            <a href="/<?= $adminPrefix ?>/notice/edit/new" class="btn btn-primary">发布通知</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>内容摘要</th>
                        <th>优先级</th>
                        <th>显示时间</th>
                        <th>语言</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="7" class="text-center text-muted">暂无通知</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td style="max-width:300px;"><?= htmlspecialchars(mb_substr(strip_tags($row['content'] ?? ''), 0, 60)) ?>...</td>
                                <td><?= (int)($row['priority'] ?? 0) ?></td>
                                <td><?= $row['display_from'] ?? '-' ?> ~ <?= $row['display_to'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($row['lang'] ?? '') ?></td>
                                <td><?= ($row['status'] ?? '') === 'enabled' ? '启用' : '禁用' ?></td>
                                <td>
                                    <a href="/<?= $adminPrefix ?>/notice/edit/<?= (int)$row['id'] ?>">编辑</a>
                                    <a href="/<?= $adminPrefix ?>/notice/toggle/<?= (int)$row['id'] ?>"><?= ($row['status'] ?? '') === 'enabled' ? '禁用' : '启用' ?></a>
                                    <a href="/<?= $adminPrefix ?>/notice/delete/<?= (int)$row['id'] ?>" onclick="return confirm('确定删除？');">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
