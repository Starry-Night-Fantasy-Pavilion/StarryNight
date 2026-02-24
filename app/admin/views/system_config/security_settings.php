<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title">安全设置</h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <label class="form-label">IP白名单</label>
                <textarea name="security_ip_whitelist" class="form-control" rows="3" placeholder="每行一个IP地址或IP段，例如：192.168.1.1 或 192.168.1.0/24"><?= htmlspecialchars($data['security_ip_whitelist'] ?? '') ?></textarea>
                <small class="form-text text-muted">留空表示不限制</small>
            </div>
            <div class="mb-3">
                <label class="form-label">IP黑名单</label>
                <textarea name="security_ip_blacklist" class="form-control" rows="3" placeholder="每行一个IP地址或IP段"><?= htmlspecialchars($data['security_ip_blacklist'] ?? '') ?></textarea>
                <small class="form-text text-muted">黑名单中的IP将被禁止访问</small>
            </div>
            <div class="mb-3">
                <label class="form-label">双因素认证</label>
                <div>
                    <label>
                        <input type="checkbox" name="security_2fa_enabled" value="1" <?= !empty($data['security_2fa_enabled']) ? 'checked' : '' ?>>
                        启用双因素认证（2FA）
                    </label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">密码策略（JSON格式）</label>
                <textarea name="security_password_policy" class="form-control" rows="5" placeholder='{"min_length":8,"require_upper":1,"require_lower":1,"require_number":1,"require_special":0}'><?= htmlspecialchars($data['security_password_policy'] ?? '') ?></textarea>
                <small class="form-text text-muted">
                    配置项说明：min_length（最小长度）、require_upper（需要大写字母）、require_lower（需要小写字母）、require_number（需要数字）、require_special（需要特殊字符）
                </small>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>
