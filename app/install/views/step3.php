<!DOCTYPE html>
<html>
<head>
    <title>星夜阁 - 安装向导 - RabbitMQ 配置</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/static/install/css/style.css">
</head>
<body class="install-page step-3">
    <div class="install-wrapper">
        <?php include __DIR__ . '/_partials/sidebar.php'; ?>

        <div class="install-main">
            <form action="?step=3" method="post" style="display: contents;">
                <div class="install-content">
                    <h1>RabbitMQ 配置</h1>
                    <p class="description">请填写您的 RabbitMQ 连接信息。RabbitMQ 用于异步任务处理。</p>

                    <?php 
                    $config = $_SESSION['install_config'] ?? [];
                    if (isset($_SESSION['install_error'])): ?>
                        <div class="error-message" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--fail-color); color: #ff9999; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                            <?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-wrapper">
                        <fieldset>
                            <legend>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span>RabbitMQ 设置</span>
                                    <label class="switch" style="position: relative; display: inline-block; width: 40px; height: 20px; margin-bottom: 0;">
                                        <input type="checkbox" name="rabbitmq[enabled]" value="1" id="rabbitmq_enabled" <?php echo (!isset($config['rabbitmq']['enabled']) || $config['rabbitmq']['enabled'] == '1') ? 'checked' : ''; ?> onchange="toggleRabbitMQ(this.checked)">
                                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #333; transition: .4s; border-radius: 20px;"></span>
                                    </label>
                                </div>
                            </legend>
                            <div id="rabbitmq_fields" class="form-grid" style="<?php echo (isset($config['rabbitmq']['enabled']) && $config['rabbitmq']['enabled'] == '0') ? 'display: none;' : ''; ?>">
                                <div class="form-item">
                                    <label for="rabbitmq_host" class="required">RabbitMQ 地址</label>
                                    <input type="text" id="rabbitmq_host" name="rabbitmq[host]" value="<?php echo htmlspecialchars($config['rabbitmq']['host'] ?? '127.0.0.1'); ?>" <?php echo (!isset($config['rabbitmq']['enabled']) || $config['rabbitmq']['enabled'] == '1') ? 'required' : ''; ?>>
                                </div>
                                <div class="form-item">
                                    <label for="rabbitmq_port" class="required">RabbitMQ 端口</label>
                                    <input type="text" id="rabbitmq_port" name="rabbitmq[port]" value="<?php echo htmlspecialchars($config['rabbitmq']['port'] ?? '5672'); ?>" <?php echo (!isset($config['rabbitmq']['enabled']) || $config['rabbitmq']['enabled'] == '1') ? 'required' : ''; ?>>
                                </div>
                                <div class="form-item">
                                    <label for="rabbitmq_user" class="required">RabbitMQ 用户</label>
                                    <input type="text" id="rabbitmq_user" name="rabbitmq[user]" value="<?php echo htmlspecialchars($config['rabbitmq']['user'] ?? 'guest'); ?>" <?php echo (!isset($config['rabbitmq']['enabled']) || $config['rabbitmq']['enabled'] == '1') ? 'required' : ''; ?>>
                                </div>
                                <div class="form-item">
                                    <label for="rabbitmq_pass">RabbitMQ 密码</label>
                                    <div class="input-group">
                                        <input type="password" id="rabbitmq_pass" name="rabbitmq[pass]" value="<?php echo htmlspecialchars($config['rabbitmq']['pass'] ?? 'guest'); ?>">
                                        <button type="button" class="btn-random" onclick="generateRandom('rabbitmq_pass', 16)" title="随机生成">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="actions">
                    <a href="?step=2" class="btn btn-secondary">
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
    <style>
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider:before {
            position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary-color) !important; }
        input:checked + .slider:before { transform: translateX(20px); }
    </style>
    <script>
        function toggleRabbitMQ(enabled) {
            const fields = document.getElementById('rabbitmq_fields');
            const inputs = fields.querySelectorAll('input');
            fields.style.display = enabled ? 'grid' : 'none';
            inputs.forEach(input => {
                if (enabled) {
                    if (input.id !== 'rabbitmq_pass') input.setAttribute('required', '');
                } else {
                    input.removeAttribute('required');
                }
            });
        }

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
