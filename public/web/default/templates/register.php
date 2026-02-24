<?php
$siteNameForAuth = (string)($site_name ?? '');
$siteLogoForAuth = (string)($site_logo ?? '');
$registerEmailEnabled = ($register_email_enabled ?? '1') !== '0';
$registerPhoneEnabled = ($register_phone_enabled ?? '1') !== '0';
$registerDefaultMethod = $register_default_method ?? 'email';

$showTabs = $registerEmailEnabled && $registerPhoneEnabled;
$defaultTab = $registerDefaultMethod === 'phone' ? 'phone' : 'email';
?>

<style>
:root {
    --admin-login-primary: #3b82f6;
    --admin-login-primary-hover: #60a5fa;
    --admin-login-text: #ffffff;
    --admin-login-text-muted: rgba(255, 255, 255, 0.7);
    --admin-login-border: rgba(255, 255, 255, 0.18);
    --glass-alpha: 0.14;
    --glass-tint: 255, 255, 255;
    --admin-login-card-bg: rgba(var(--glass-tint), var(--glass-alpha));
    --admin-login-input-bg: rgba(0, 0, 0, 0.3);
    --admin-login-error-bg: rgba(239, 68, 68, 0.12);
    --admin-login-error-border: rgba(239, 68, 68, 0.45);
    --admin-login-error-text: #ff9999;
    --admin-login-success-bg: rgba(16, 185, 129, 0.12);
    --admin-login-success-border: rgba(16, 185, 129, 0.45);
    --admin-login-success-text: #6ee7b7;
    --admin-login-radius-lg: 20px;
    --admin-login-radius-md: 10px;
    --admin-login-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
}

body.page-register {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI",
        Roboto, "Noto Sans SC", sans-serif;
    background-image: url('/web/default/assets/images/IMG_20260217_233007.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: var(--admin-login-text);
    /* 允许垂直滚动，避免手机端内容被截断 */
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* 隐藏滚动条但保留滚动能力 */
body.page-register {
    scrollbar-width: none;      /* Firefox */
    -ms-overflow-style: none;   /* IE/Edge legacy */
}

body.page-register::-webkit-scrollbar {
    display: none;              /* Chrome/Safari */
}

body.page-register * {
    box-sizing: border-box;
}

.register-wrapper {
    width: 100%;
    max-width: 680px;
    margin: 0 auto;
}

.register-card {
    width: 100%;
    max-width: 100%;
    background: var(--admin-login-card-bg);
    backdrop-filter: blur(18px) saturate(150%);
    -webkit-backdrop-filter: blur(18px) saturate(150%);
    border-radius: var(--admin-login-radius-lg);
    border: 1px solid var(--admin-login-border);
    box-shadow: var(--admin-login-shadow);
    padding: 16px 24px 14px;
}

.register-inner {
    width: 100%;
    max-width: 100%;
}

.register-header {
    text-align: center;
    margin-bottom: 14px;
}

.register-logo {
    margin-bottom: 6px;
    display: flex;
    justify-content: center;
}

.register-logo img {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    object-fit: contain;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.55);
}

.register-title {
    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 3px;
    background: linear-gradient(135deg, var(--admin-login-primary), #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.register-subtitle {
    font-size: 0.82rem;
    color: var(--admin-login-text-muted);
    line-height: 1.3;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.form-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: var(--admin-login-input-bg);
    color: var(--admin-login-text);
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease,
        background-color 0.2s ease, transform 0.08s ease;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.45);
}

.form-control:focus {
    border-color: var(--admin-login-primary);
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.7);
    background: rgba(0, 0, 0, 0.45);
    transform: translateY(-1px);
}

.error-message {
    background: var(--admin-login-error-bg);
    border: 1px solid var(--admin-login-error-border);
    color: var(--admin-login-error-text);
    padding: 8px 12px;
    border-radius: var(--admin-login-radius-md);
    font-size: 0.85rem;
    margin-bottom: 8px;
    text-align: center;
}

.success-message {
    background: var(--admin-login-success-bg);
    border: 1px solid var(--admin-login-success-border);
    color: var(--admin-login-success-text);
    padding: 8px 12px;
    border-radius: var(--admin-login-radius-md);
    font-size: 0.85rem;
    margin-bottom: 8px;
    text-align: center;
}

.btn-register {
    width: 100%;
    margin-top: 2px;
    padding: 12px 18px;
    border-radius: var(--admin-login-radius-md);
    border: none;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: #ffffff;
    background: linear-gradient(
        135deg,
        var(--admin-login-primary),
        var(--admin-login-primary-hover)
    );
    box-shadow: 0 10px 26px rgba(59, 130, 246, 0.55);
    transition: transform 0.12s ease, box-shadow 0.15s ease,
        filter 0.15s ease;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(59, 130, 246, 0.75);
    filter: brightness(1.03);
}

.btn-register:active {
    transform: translateY(0);
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.5);
}

.register-footer {
    margin-top: 12px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--admin-login-text-muted);
}

.register-footer a {
    color: #fff;
    text-decoration: none;
}

.register-footer a:hover {
    text-decoration: underline;
}

.register-tabs {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 12px;
}

.register-tab {
    padding: 8px 16px;
    border-radius: 999px;
    border: 1px solid var(--admin-login-border);
    background: rgba(0, 0, 0, 0.3);
    color: var(--admin-login-text-muted);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.register-tab:hover {
    background: rgba(0, 0, 0, 0.4);
    color: var(--admin-login-text);
}

.register-tab.active {
    border-color: var(--admin-login-primary);
    background: rgba(59, 130, 246, 0.2);
    color: #fff;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.verify-code-group {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.verify-code-group .form-control {
    flex: 1;
}

.btn-send-code {
    flex: 0 0 auto;
    padding: 10px 16px;
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: rgba(0, 0, 0, 0.3);
    color: var(--admin-login-text);
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-send-code:hover {
    background: rgba(0, 0, 0, 0.45);
    border-color: var(--admin-login-primary);
}

.btn-send-code:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.field-optional {
    font-size: 0.8rem;
    color: var(--admin-login-text-muted);
    margin-top: 4px;
}
.code-message {
    margin-top: 4px;
    font-size: 0.82rem;
    min-height: 1.2em;
}
.code-message.error {
    color: #fecaca;
}
.code-message.success {
    color: #bbf7d0;
}

/* 小屏幕：头部恢复纵向居中，避免横向拥挤 */
@media (max-width: 480px) {
    .register-header {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }

    .register-headings {
        text-align: center;
    }
}

/* 验证码区域样式 */
.captcha-group {
    /* 使用 form-group 的默认样式 */
}

.captcha-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.captcha-group .captcha-widget {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 8px !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
}

.captcha-group .captcha-image {
    display: none !important;
}

.captcha-group .captcha-input {
    order: 1;
    flex: 1;
    width: 100%;
    padding: 10px 14px;
    font-size: 0.95rem;
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: var(--admin-login-input-bg);
    color: var(--admin-login-text);
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease,
        background-color 0.2s ease, transform 0.08s ease;
}

.captcha-group .captcha-input::placeholder {
    color: rgba(255, 255, 255, 0.45);
}

.captcha-group .captcha-input:focus {
    border-color: var(--admin-login-primary);
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.7);
    background: rgba(0, 0, 0, 0.45);
    transform: translateY(-1px);
}

.captcha-group .captcha-question {
    order: 2;
    margin: 0;
    padding: 12px 16px;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.85);
    white-space: nowrap;
    font-weight: 500;
    flex-shrink: 0;
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: rgba(0, 0, 0, 0.35);
    text-align: center;
    min-width: 110px;
}

@media (max-width: 768px) {
    body.page-register {
        padding: 16px;
        align-items: flex-start;
    }
    
    .register-wrapper {
        margin: 0 auto;
        max-width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .register-card {
        padding: 24px 20px 20px;
    }
    
    .register-title {
        font-size: 1.75rem;
    }
    
    .register-form {
        gap: 12px;
    }
}

@media (max-width: 480px) {
    body.page-register {
        padding: 12px;
    }
    
    .register-card {
        padding: 20px 16px 18px;
    }
    
    .register-title {
        font-size: 1.6rem;
    }
    
    .register-header {
        margin-bottom: 16px;
    }
    
    .register-form {
        gap: 10px;
    }
}
</style>

<div class="register-wrapper">
    <div class="register-card">
        <div class="register-inner">
            <div class="register-header">
                <div class="register-logo">
                    <?php if (!empty($siteLogoForAuth)): ?>
                        <img src="<?= htmlspecialchars($siteLogoForAuth) ?>" alt="<?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?>">
                    <?php endif; ?>
                </div>
                <div class="register-headings">
                    <h1 class="register-title">用户注册</h1>
                    <p class="register-subtitle">欢迎来到 <?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?>，请填写注册信息</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars((string) $error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?= htmlspecialchars((string) $success) ?>
                </div>
            <?php endif; ?>

            <?php if ($showTabs): ?>
            <div class="register-tabs">
                <button type="button" class="register-tab <?= $defaultTab === 'email' ? 'active' : '' ?>" data-tab="email">
                    <span>邮箱注册</span>
                </button>
                <button type="button" class="register-tab <?= $defaultTab === 'phone' ? 'active' : '' ?>" data-tab="phone">
                    <span>手机号注册</span>
                </button>
            </div>
            <?php endif; ?>

            <form class="register-form" method="post" action="<?= htmlspecialchars((string) ($action ?? '/register')) ?>">
                <input type="hidden" name="register_method" id="register_method" value="<?= htmlspecialchars($defaultTab) ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">用户名<span style="color:#f97373;"> *</span></label>
                    <input id="username" type="text" class="form-control" name="username" autocomplete="username" placeholder="请输入用户名" required autofocus>
                </div>
                
                <?php if ($registerEmailEnabled): ?>
                <div class="form-group register-email-field" style="<?= $showTabs && $defaultTab !== 'email' ? 'display:none;' : '' ?>">
                    <label for="email" class="form-label">邮箱<span style="color:#f97373;"> *</span></label>
                    <input id="email" type="email" class="form-control" name="email" autocomplete="email" placeholder="请输入邮箱地址" <?= $registerEmailEnabled && !$registerPhoneEnabled ? 'required' : '' ?>>
                </div>
                <?php endif; ?>
                
                <?php if ($registerPhoneEnabled): ?>
                <div class="form-group register-phone-field" style="<?= $showTabs && $defaultTab !== 'phone' ? 'display:none;' : '' ?>">
                    <label for="phone" class="form-label">手机号<span style="color:#f97373;"> *</span></label>
                    <input id="phone" type="tel" class="form-control" name="phone" autocomplete="tel" placeholder="请输入11位手机号" <?= $registerPhoneEnabled && !$registerEmailEnabled ? 'required' : '' ?>>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="verify_code" class="form-label">验证码<span style="color:#f97373;"> *</span></label>
                    <div class="verify-code-group">
                        <input id="verify_code" type="text" class="form-control" name="verify_code" placeholder="请输入验证码" required>
                        <button type="button" id="send-register-code" class="btn-send-code">发送验证码</button>
                    </div>
                    <small class="field-optional">将根据当前选择的注册方式，发送到对应的邮箱或手机号</small>
                    <div id="register-code-message" class="code-message"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">密码<span style="color:#f97373;"> *</span></label>
                        <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" placeholder="请设置密码" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm" class="form-label">确认密码<span style="color:#f97373;"> *</span></label>
                        <input id="password_confirm" type="password" class="form-control" name="password_confirm" autocomplete="new-password" placeholder="请再次输入密码" required>
                    </div>
                </div>
                
                <?php if (!empty($captcha_html ?? '')): ?>
                <div class="form-group captcha-group">
                    <label class="form-label">安全验证<span style="color:#f97373;"> *</span></label>
                    <div class="captcha-inner">
                        <?= $captcha_html ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-register">注册</button>
            </form>

            <div class="register-footer">
                <span>已有账号？ <a href="/login">立即登录</a></span>
            </div>
        </div>
    </div>
</div>

<?php if ($showTabs): ?>
<script>
(function() {
    var tabs = document.querySelectorAll('.register-tab');
    var emailField = document.querySelector('.register-email-field');
    var phoneField = document.querySelector('.register-phone-field');
    var emailInput = document.getElementById('email');
    var phoneInput = document.getElementById('phone');
    var methodInput = document.getElementById('register_method');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabType = this.getAttribute('data-tab');
            
            tabs.forEach(function(t) { t.classList.remove('active'); });
            this.classList.add('active');
            
            if (tabType === 'email') {
                if (emailField) emailField.style.display = '';
                if (phoneField) phoneField.style.display = 'none';
                if (emailInput) emailInput.required = true;
                if (phoneInput) phoneInput.required = false;
                if (methodInput) methodInput.value = 'email';
            } else {
                if (emailField) emailField.style.display = 'none';
                if (phoneField) phoneField.style.display = '';
                if (emailInput) emailInput.required = false;
                if (phoneInput) phoneInput.required = true;
                if (methodInput) methodInput.value = 'phone';
            }
        });
    });
})();
</script>
<?php endif; ?>

<script>
(function() {
    var sendBtn = document.getElementById('send-register-code');
    if (!sendBtn) return;

    var methodInput = document.getElementById('register_method');
    var emailInput = document.getElementById('email');
    var phoneInput = document.getElementById('phone');
    var msgEl = document.getElementById('register-code-message');
    var sending = false;
    var countdown = 0;
    var timerId = null;

    function showMsg(text, type) {
        if (!msgEl) return;
        msgEl.textContent = text || '';
        msgEl.classList.remove('error', 'success');
        if (type) {
            msgEl.classList.add(type);
        }
    }

    sendBtn.addEventListener('click', function() {
        if (sending || countdown > 0) return;
        var method = methodInput ? (methodInput.value || 'email') : 'email';
        var target = '';

        if (method === 'phone' && phoneInput) {
            target = (phoneInput.value || '').trim();
        } else if (emailInput) {
            target = (emailInput.value || '').trim();
        }

        if (!target) {
            showMsg(method === 'phone' ? '请先填写手机号' : '请先填写邮箱地址', 'error');
            return;
        }

        sending = true;
        var originalText = sendBtn.textContent;
        sendBtn.disabled = true;
        sendBtn.textContent = '发送中...';

        var body = 'method=' + encodeURIComponent(method) + '&target=' + encodeURIComponent(target);

        fetch('/register/send-code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function(res) {
            // 尝试解析JSON，即使HTTP状态码不是200
            return res.json().then(function(data) {
                return { ok: res.ok, status: res.status, data: data };
            }).catch(function() {
                // 如果JSON解析失败，返回错误信息
                if (!res.ok) {
                    return { ok: false, status: res.status, data: { success: false, message: '服务器错误 (HTTP ' + res.status + ')，请稍后重试' } };
                }
                return { ok: res.ok, status: res.status, data: { success: false, message: '服务器响应格式错误，请稍后重试' } };
            });
        }).then(function(result) {
            var data = result.data;
            if (data && data.success) {
                showMsg(data.message || '验证码已发送，请注意查收', 'success');

                // 启动 60 秒倒计时（仅在成功时）
                countdown = 60;
                sendBtn.disabled = true;
                sendBtn.textContent = countdown + ' 秒后可重发';
                if (timerId) {
                    clearInterval(timerId);
                }
                timerId = setInterval(function() {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(timerId);
                        timerId = null;
                        sendBtn.disabled = false;
                        sendBtn.textContent = originalText;
                        showMsg('', null);
                    } else {
                        sendBtn.textContent = countdown + ' 秒后可重发';
                    }
                }, 1000);
            } else {
                // 显示后端返回的具体错误信息
                var errorMsg = (data && data.message) ? data.message : '发送失败，请稍后重试';
                showMsg(errorMsg, 'error');
                sendBtn.disabled = false;
                sendBtn.textContent = originalText;
            }
        }).catch(function(err) {
            console.error('发送验证码错误:', err);
            showMsg('网络错误，请检查网络连接后重试', 'error');
            sendBtn.disabled = false;
            sendBtn.textContent = originalText;
        }).finally(function() {
            sending = false;
        });
    });
})();
</script>
