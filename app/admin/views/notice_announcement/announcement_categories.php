<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">公告分类</h2>
        <a href="/<?= $adminPrefix ?>/announcement/category/new" class="btn btn-primary">新增分类</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名称</th>
                        <th>排序</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="4" class="text-center text-muted">暂无分类</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                                <td><?= (int)($row['sort_order'] ?? 0) ?></td>
                                <td>
                                    <a href="/<?= $adminPrefix ?>/announcement/category/<?= (int)$row['id'] ?>">编辑</a>
                                    <a href="/<?= $adminPrefix ?>/announcement/category/<?= (int)$row['id'] ?>/delete" onclick="return confirm('确定删除？');">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
