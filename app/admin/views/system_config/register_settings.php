<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 class="sysconfig-card-title">注册设置</h2></div>
    <div class="card-body">
        <form method="POST" class="form-horizontal">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="register_email_enabled" id="register_email_enabled" value="1" <?= (!isset($data['register_email_enabled']) || $data['register_email_enabled'] === '1' || $data['register_email_enabled'] === '') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="register_email_enabled">启用邮箱注册</label>
                </div>
                <small class="text-muted">允许用户使用邮箱地址注册账号</small>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="register_phone_enabled" id="register_phone_enabled" value="1" <?= (!isset($data['register_phone_enabled']) || $data['register_phone_enabled'] === '1' || $data['register_phone_enabled'] === '') ? 'checked' : '' ?>>
                    <label class="form-check-label" for="register_phone_enabled">启用手机号注册</label>
                </div>
                <small class="text-muted">允许用户使用手机号码注册账号</small>
            </div>
            <div class="mb-3">
                <label class="form-label">默认注册方式</label>
                <select name="register_default_method" class="form-control">
                    <option value="email" <?= ($data['register_default_method'] ?? 'email') === 'email' ? 'selected' : '' ?>>邮箱注册</option>
                    <option value="phone" <?= ($data['register_default_method'] ?? 'email') === 'phone' ? 'selected' : '' ?>>手机号注册</option>
                </select>
                <small class="text-muted">用户打开注册页面时默认显示的注册方式</small>
            </div>
            <div class="mb-3">
                <label class="form-label">验证码有效时间（分钟）</label>
                <input type="number" name="register_code_expire_minutes" class="form-control" min="1" max="60" value="<?= htmlspecialchars($data['register_code_expire_minutes'] !== '' ? $data['register_code_expire_minutes'] : '10') ?>">
                <small class="text-muted">用于注册时发送的邮箱/短信验证码有效时长，默认 10 分钟。</small>
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>

