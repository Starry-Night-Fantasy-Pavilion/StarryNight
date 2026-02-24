<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title">存储配置</h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <label class="form-label">本地存储路径</label>
                <input type="text" name="storage_local_path" class="form-control" value="<?= htmlspecialchars($data['storage_local_path'] ?? '') ?>" placeholder="例如：/storage/app">
            </div>
            <div class="mb-3">
                <label class="form-label">清理策略</label>
                <textarea name="storage_cleanup_policy" class="form-control" rows="3" placeholder="存储清理策略（JSON格式）"><?= htmlspecialchars($data['storage_cleanup_policy'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>
