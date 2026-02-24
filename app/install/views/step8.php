<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 安装完成</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-8">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <div class="install-content">
                <h1>🎉 安装成功！</h1>
                <p class="description">恭喜您，星夜阁已经成功安装！为了安全，安装程序将自动锁定。</p>

                <div class="completion-info">
                    <div class="info-item">
                        <span class="label">管理员账号:</span>
                        <span class="value"><?= htmlspecialchars($admin_username ?? 'admin') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label">您的网站首页:</span>
                        <span class="value"><a href="<?= htmlspecialchars($site_url ?? '/') ?>" target="_blank"><?= htmlspecialchars($site_url ?? '/') ?></a></span>
                    </div>
                    <div class="info-item">
                        <span class="label">后台管理入口:</span>
                        <span class="value"><a href="<?= htmlspecialchars($full_admin_url ?? '/admin') ?>" target="_blank"><?= htmlspecialchars($full_admin_url ?? '/admin') ?></a></span>
                    </div>
                </div>

                <div class="security-warning">
                    <h4>重要安全提示</h4>
                    <p>为了您站点的安全，请立即删除 `public/install` 目录，或将 `install.lock` 文件妥善保管。</p>
                </div>
            </div>
            <div class="actions">
                <a href="<?= htmlspecialchars($full_admin_url ?? '/admin') ?>" class="btn btn-primary">
                    前往后台
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
