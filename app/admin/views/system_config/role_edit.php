<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title"><?= $role ? '编辑角色' : '新增角色' ?></h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <label class="form-label">角色名称</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">描述</label>
                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">数据范围</label>
                <select name="data_scope" class="form-control">
                    <option value="all" <?= ($role['data_scope'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部数据</option>
                    <option value="custom" <?= ($role['data_scope'] ?? '') === 'custom' ? 'selected' : '' ?>>自定义</option>
                    <option value="dept" <?= ($role['data_scope'] ?? '') === 'dept' ? 'selected' : '' ?>>本部门</option>
                    <option value="self" <?= ($role['data_scope'] ?? '') === 'self' ? 'selected' : '' ?>>仅本人</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">排序</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($role['sort_order'] ?? 0) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">权限配置</label>
                <div class="sysconfig-permissions-box">
                    <?php foreach ($allPermissionKeys as $key => $label): ?>
                        <div class="form-check sysconfig-permission-item">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= htmlspecialchars($key) ?>" id="perm_<?= htmlspecialchars($key) ?>" <?= in_array($key, $permissions ?? []) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="perm_<?= htmlspecialchars($key) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/system/roles" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
