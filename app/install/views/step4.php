<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - Redis 配置</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-4">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <form action="?step=4" method="post" style="display: contents;">
                <div class="install-content">
                    <h1>Redis 配置</h1>
                    <p class="description">请填写您的 Redis 连接信息。Redis 用于系统缓存和队列。</p>

                    <?php 
                    $config = $_SESSION['install_config'] ?? [];
                    if (isset($_SESSION['install_error'])): ?>
                        <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--fail-color); color: #ff9999; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                            <?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-wrapper">
                        <fieldset>
                            <legend>Redis 设置</legend>
                            <div class="form-grid">
                                <div class="form-item">
                                    <label for="redis_host" class="required">Redis 地址</label>
                                    <input type="text" id="redis_host" name="redis[host]" value="<?php echo htmlspecialchars($config['redis']['host'] ?? '127.0.0.1'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="redis_port" class="required">Redis 端口</label>
                                    <input type="text" id="redis_port" name="redis[port]" value="<?php echo htmlspecialchars($config['redis']['port'] ?? '6379'); ?>" required>
                                </div>
                                <div class="form-item">
                                    <label for="redis_pass">Redis 密码</label>
                                    <div class="input-group">
                                        <input type="password" id="redis_pass" name="redis[pass]" value="<?php echo htmlspecialchars($config['redis']['pass'] ?? ''); ?>" placeholder="如果为空则不设置密码">
                                        <button type="button" class="btn-random" onclick="generateRandom('redis_pass', 16)" title="随机生成">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="actions">
                    <a href="?step=3" class="btn btn-secondary">
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
            if (input.type === 'password') {
                input.type = 'text';
            }
        }
    </script>
</body>
</html>
