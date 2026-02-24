<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header sysconfig-card-header-row">
        <h2 class="sysconfig-card-title">角色管理</h2>
        <a href="/<?= $adminPrefix ?>/system/role/new" class="btn btn-primary">新增角色</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>角色名称</th>
                        <th>描述</th>
                        <th>数据范围</th>
                        <th>排序</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr><td colspan="6" class="text-center text-muted">暂无角色，请先新增</td></tr>
                    <?php else: ?>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= (int)$role['id'] ?></td>
                                <td><?= htmlspecialchars($role['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                                <td><?= htmlspecialchars($role['data_scope'] ?? 'all') ?></td>
                                <td><?= (int)($role['sort_order'] ?? 0) ?></td>
                                <td>
                                    <a href="/<?= $adminPrefix ?>/system/role/<?= (int)$role['id'] ?>">编辑</a>
                                    <?php if (!empty($role['is_system'])): ?>
                                        <span class="text-muted sysconfig-inline-muted">系统内置</span>
                                    <?php else: ?>
                                        <a href="/<?= $adminPrefix ?>/system/role/<?= (int)$role['id'] ?>/delete" onclick="return confirm('确定要删除此角色吗？');" class="sysconfig-link-danger">删除</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
