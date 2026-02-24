<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title">基础设置</h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <label class="form-label">网站名称</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($data['site_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">网站 Logo URL</label>
                <input type="text" name="site_logo" class="form-control" value="<?= htmlspecialchars($data['site_logo'] ?? '') ?>" placeholder="例如：/static/logo/logo.png">
            </div>
            <div class="mb-3">
                <label class="form-label">ICP 备案信息</label>
                <input type="text" name="icp_info" class="form-control" value="<?= htmlspecialchars($data['icp_info'] ?? '') ?>" placeholder="例如：京ICP备12345678号">
            </div>
            <div class="mb-3">
                <label class="form-label">联系方式</label>
                <textarea name="contact_info" class="form-control" rows="3" placeholder="联系方式信息（JSON格式或纯文本）"><?= htmlspecialchars($data['contact_info'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">客服配置</label>
                <textarea name="customer_service_config" class="form-control" rows="3" placeholder="客服配置（JSON格式）"><?= htmlspecialchars($data['customer_service_config'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">货币名称</label>
                <input type="text" name="currency_name" class="form-control" value="<?= htmlspecialchars($data['currency_name'] ?? '星夜币') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>
