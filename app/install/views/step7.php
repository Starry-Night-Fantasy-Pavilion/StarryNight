<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 安装完成</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-7">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <div class="install-content" style="text-align: center; padding: 40px 20px;">
                <div class="success-icon" style="margin-bottom: 20px;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--success-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h1>安装成功！</h1>
                <p class="description">星夜阁已成功安装在您的服务器上。为了安全起见，请务必删除 <code>app/install</code> 目录。</p>

                <div class="info-box" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 20px; margin: 30px 0; text-align: left;">
                    <h3 style="margin-top: 0; color: var(--primary-color);">管理后台</h3>
                    <p>请务必牢记您的管理后台访问地址和账号信息：</p>
                    <div style="background: #000; padding: 15px; border-radius: 6px; margin: 15px 0;">
                        <div style="margin-bottom: 10px;">
                            <span style="color: #888; margin-right: 10px;">后台地址:</span>
                            <code style="word-break: break-all; color: var(--primary-color);"><?php echo htmlspecialchars($full_admin_url); ?></code>
                        </div>
                        <div>
                            <span style="color: #888; margin-right: 10px;">管理账号:</span>
                            <code style="color: #fff;"><?php echo htmlspecialchars($admin_username); ?></code>
                        </div>
                    </div>
                    <p style="font-size: 0.9em; color: #ff9999; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 6px;">
                        <strong>安全提示：</strong> 为了您的站点安全，请在登录后及时修改默认配置，并手动删除 <code>app/install</code> 目录。
                    </p>
                </div>

                <div class="actions" style="justify-content: center; border-top: none; padding-top: 0;">
                    <a href="<?php echo htmlspecialchars($site_url); ?>" class="btn btn-secondary">访问首页</a>
                    <a href="<?php echo htmlspecialchars($full_admin_url); ?>" class="btn btn-primary">进入后台</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
