<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 数据库配置</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-6">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <form action="?step=6" method="post" style="display: contents;">
                <div class="install-content">
                    <h1>数据库配置</h1>
                    <p class="description">请填写您的数据库连接信息。</p>

                    <?php 
                    $config = $_SESSION['install_config'] ?? [];
                    if (isset($_SESSION['install_error'])): ?>
                        <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--fail-color); color: #ff9999; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                            <?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-wrapper">
                        <fieldset>
                            <legend>数据库设置</legend>
                            <div class="form-grid">
                                <div class="form-item">
                                    <label for="db_host" class="required">数据库地址</label>
                                    <input type="text" id="db_host" name="db[host]" value="<?php echo htmlspecialchars($config['db']['host'] ?? '127.0.0.1'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="db_port" class="required">数据库端口</label>
                                    <input type="text" id="db_port" name="db[port]" value="<?php echo htmlspecialchars($config['db']['port'] ?? '3306'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="db_name" class="required">数据库名称</label>
                                    <input type="text" id="db_name" name="db[name]" value="<?php echo htmlspecialchars($config['db']['name'] ?? 'starry_night'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="db_user" class="required">数据库用户</label>
                                    <input type="text" id="db_user" name="db[user]" value="<?php echo htmlspecialchars($config['db']['user'] ?? 'root'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="db_pass">数据库密码</label>
                                    <input type="password" id="db_pass" name="db[pass]" value="<?php echo htmlspecialchars($config['db']['pass'] ?? ''); ?>" placeholder="如果为空则不设置密码">
                                </div>
                                <div class="form-item">
                                    <label for="db_prefix" class="required">表前缀</label>
                                    <input type="text" id="db_prefix" name="db[prefix]" value="<?php echo htmlspecialchars($config['db']['prefix'] ?? 'sn_'); ?>" required>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="actions">
                    <a href="?step=5" class="btn btn-secondary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                        上一步
                    </a>
                    <button type="submit" class="btn btn-primary">
                        下一步
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
