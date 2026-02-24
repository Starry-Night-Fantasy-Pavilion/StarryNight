<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - 管理员设置</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-2">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <form action="?step=2" method="post" style="display: contents;">
                <div class="install-content">
                    <h1>管理员设置</h1>
                    <p class="description">请创建您的管理员账号和设置站点基本信息。</p>

                    <?php 
                    $draft = $_SESSION['install_admin_draft'] ?? [];
                    if (isset($_SESSION['install_error'])): ?>
                        <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--fail-color); color: #ff9999; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                            <?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-wrapper">
                        <fieldset>
                            <legend>站点信息</legend>
                            <div class="form-grid">
                                <div class="form-item">
                                    <label for="site_name" class="required">站点名称</label>
                                    <input type="text" id="site_name" name="site[name]" value="<?php echo htmlspecialchars($draft['site_name'] ?? '星夜阁'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="admin_path" class="required">后台管理路径</label>
                                    <div class="input-group">
                                        <input type="text" id="admin_path" name="admin_path" value="<?php echo htmlspecialchars($draft['admin_path'] ?? 'admin'); ?>" required>
                                        <button type="button" class="btn-random" onclick="generateRandom('admin_path', 8)" title="随机生成">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>管理员账号</legend>
                            <div class="form-grid">
                                <div class="form-item">
                                    <label for="admin_user" class="required">管理员用户名</label>
                                    <input type="text" id="admin_user" name="admin[username]" value="<?php echo htmlspecialchars($draft['username'] ?? 'admin'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="admin_email" class="required">管理员邮箱</label>
                                    <input type="email" id="admin_email" name="admin[email]" value="<?php echo htmlspecialchars($draft['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="admin_pass" class="required">管理员密码</label>
                                    <div class="input-group">
                                        <input type="password" id="admin_pass" name="admin[password]" required>
                                        <button type="button" class="btn-random" onclick="generateRandom('admin_pass', 16)" title="随机生成">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-item">
                                    <label for="admin_pass_confirm" class="required">确认密码</label>
                                    <input type="password" id="admin_pass_confirm" name="admin[password_confirm]" required>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="actions">
                    <a href="?step=1" class="btn btn-secondary">
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
    <script>
        function generateRandom(id, length) {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            let retVal = "";
            for (let i = 0, n = charset.length; i < length; ++i) {
                retVal += charset.charAt(Math.floor(Math.random() * n));
            }
            const input = document.getElementById(id);
            input.value = retVal;
            
            // 如果是密码，同步到确认密码并显示明文
            if (id === 'admin_pass') {
                input.type = 'text';
                const confirm = document.getElementById('admin_pass_confirm');
                if (confirm) {
                    confirm.value = retVal;
                    confirm.type = 'text';
                }
            }
            // 如果是后台路径，显示明文
            if (id === 'admin_path') {
                input.type = 'text';
            }
        }
    </script>
</body>
</html>
