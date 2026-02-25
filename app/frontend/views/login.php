<?php
$siteNameForAuth = (string)($siteName ?? '');
$siteLogoForAuth = (string)($site_logo ?? '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '用户登录') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php 
    use app\services\ThemeManager;
    use app\config\FrontendConfig;
    $themeManager = new ThemeManager();
    $activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
    $themeBasePath = FrontendConfig::getThemePath($activeThemeId);
    // 登录页样式统一走当前启用的前台主题包
    $loginCssUrl = FrontendConfig::getThemeCssUrl('style.css', $activeThemeId, FrontendConfig::CACHE_VERSION);
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($loginCssUrl) ?>">
</head>
<body class="page-login">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="auth-brand-inner">
                <div class="brand-logo">
                    <?php if (!empty($siteLogoForAuth)): ?>
                        <img src="<?= htmlspecialchars($siteLogoForAuth) ?>" alt="<?= htmlspecialchars($siteNameForAuth) ?>">
                    <?php endif; ?>
                    <span class="brand-name"><?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?></span>
                </div>
                <h2 class="brand-headline">开启你的创作之旅</h2>
                <p class="brand-description">AI驱动的创作引擎，让每一个灵感都能绽放光芒</p>
                <div class="brand-features">
                    <div class="feature-item">
                        <div class="feature-icon">✨</div>
                        <div class="feature-text">智能写作助手</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🎨</div>
                        <div class="feature-text">风格自由切换</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">⚡</div>
                        <div class="feature-text">效率倍增工具</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-form-section">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h1 class="auth-title">欢迎回来</h1>
                    <p class="auth-subtitle">登录您的账号，继续创作</p>
                </div>

                <div class="auth-alert-container" style="min-height: 48px; margin-bottom: 24px; display: flex; align-items: center;">
                    <?php if (!empty($error)): ?>
                    <div class="auth-alert auth-alert-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span><?= htmlspecialchars((string) $error) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <form class="auth-form" method="post" action="<?= htmlspecialchars((string) ($action ?? '/login')) ?>">
                    <div class="form-field">
                        <label for="username" class="field-label">用户名 / 邮箱 / 手机号<span style="color:#f97373;"> *</span></label>
                        <input id="username" type="text" class="field-input" name="username" autocomplete="username" placeholder="请输入用户名、邮箱或手机号" required autofocus>
                    </div>
                    
                    <div class="form-field password-field">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <label for="password" class="field-label">密码<span style="color:#f97373;"> *</span></label>
                            <a href="/reset-password" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:0.85rem;">忘记密码？</a>
                        </div>
                        <div class="password-input-wrapper" style="position:relative;">
                            <input id="password" type="password" class="field-input" name="password" autocomplete="current-password" placeholder="请输入密码" required style="padding-right:48px;">
                            <button type="button" class="password-toggle-btn" aria-label="显示/隐藏密码" title="显示/隐藏密码" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:transparent;border:none;color:rgba(255,255,255,0.6);cursor:pointer;padding:8px;border-radius:6px;transition:all 0.2s ease;">
                                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;display:block;">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <?php if (!empty($captcha_html ?? '')): ?>
                    <div class="form-field">
                        <?= $captcha_html ?>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="auth-submit" id="login-submit-btn">
                        <span>登录</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <?php if (!empty($third_party_login_buttons) && is_array($third_party_login_buttons) && count($third_party_login_buttons) > 0): ?>
                <div class="auth-divider">
                    <span>或使用以下方式登录</span>
                </div>
                <div class="auth-third-party">
                    <?php foreach ($third_party_login_buttons as $button): ?>
                        <?= $button ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="auth-footer">
                </div>
            </div>
        </div>
    </div>
    <script>
    // 密码显示/隐藏功能
    (function () {
        var passwordInput = document.getElementById('password');
        var passwordToggle = document.querySelector('.password-toggle-btn');
        if (!passwordInput || !passwordToggle) return;

        var iconEye = passwordToggle.querySelector('.icon-eye');
        var iconEyeOff = passwordToggle.querySelector('.icon-eye-off');

        passwordToggle.addEventListener('click', function () {
            var isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            if (iconEye && iconEyeOff) {
                if (isPassword) {
                    iconEye.style.display = 'none';
                    iconEyeOff.style.display = 'block';
                    passwordToggle.setAttribute('aria-label', '隐藏密码');
                    passwordToggle.setAttribute('title', '隐藏密码');
                } else {
                    iconEye.style.display = 'block';
                    iconEyeOff.style.display = 'none';
                    passwordToggle.setAttribute('aria-label', '显示密码');
                    passwordToggle.setAttribute('title', '显示密码');
                }
            }
        });

        passwordToggle.addEventListener('mouseenter', function () {
            this.style.color = 'rgba(255,255,255,0.9)';
            this.style.background = 'rgba(255,255,255,0.08)';
        });

        passwordToggle.addEventListener('mouseleave', function () {
            this.style.color = 'rgba(255,255,255,0.6)';
            this.style.background = 'transparent';
        });
    })();

    // 表单提交加载状态
    (function () {
        var loginForm = document.querySelector('.auth-form');
        var submitBtn = document.getElementById('login-submit-btn');
        if (!loginForm || !submitBtn) return;

        loginForm.addEventListener('submit', function () {
            submitBtn.style.opacity = '0.7';
            submitBtn.style.pointerEvents = 'none';
            submitBtn.disabled = true;
            var span = submitBtn.querySelector('span');
            if (span) {
                span.textContent = '登录中...';
            }
        });
    })();

    // 输入框实时验证反馈
    (function () {
        var inputs = document.querySelectorAll('.field-input');
        inputs.forEach(function (input) {
            input.addEventListener('blur', function () {
                if (this.value.trim() === '' && this.hasAttribute('required')) {
                    this.style.borderColor = 'rgba(239, 68, 68, 0.5)';
                } else {
                    this.style.borderColor = '';
                }
            });

            input.addEventListener('input', function () {
                if (this.style.borderColor && this.value.trim() !== '') {
                    this.style.borderColor = '';
                }
            });
        });
    })();
    </script>
</body>
</html>
