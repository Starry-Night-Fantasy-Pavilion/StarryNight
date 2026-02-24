<?php
use app\models\Setting;

$siteName = Setting::get('site_name') ?: (string) get_env('APP_NAME', '星夜阁');
$siteLogo = Setting::get('site_logo') ?: '/static/logo/logo.png';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>星夜阁 - 后台登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/static/admin/css/login.css?v=<?= time() ?>">
</head>
<body class="admin-login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                </div>
                <h1 class="login-title">后台管理登录</h1>
                <p class="login-subtitle">欢迎回到星夜阁运营控制台</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars((string) $error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars((string) ($action ?? '')) ?>" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">管理员账号 / 邮箱</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        autocomplete="current-password"
                        required
                    >
                </div>
                <button type="submit" class="btn-login">进入后台</button>
            </form>

            <div class="login-footer">
                <span class="login-meta">星夜阁 · 后台管理系统</span>
            </div>
        </div>
    </div>
</body>
</html>
