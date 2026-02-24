<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 style="margin:0;"><?= $item ? '编辑分类' : '新增分类' ?></h2></div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">名称</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">排序</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($item['sort_order'] ?? 0) ?>">
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="/<?= $adminPrefix ?>/announcement/categories" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>
