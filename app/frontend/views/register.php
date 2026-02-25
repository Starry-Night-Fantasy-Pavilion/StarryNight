<?php
$siteNameForAuth = (string)($siteName ?? '');
$siteLogoForAuth = (string)($site_logo ?? '');
$registerEmailEnabled = ($register_email_enabled ?? '1') !== '0';
$registerPhoneEnabled = ($register_phone_enabled ?? '1') !== '0';
$registerDefaultMethod = $register_default_method ?? 'email';
$showTabs = $registerEmailEnabled && $registerPhoneEnabled;
$defaultTab = $registerDefaultMethod === 'phone' ? 'phone' : 'email';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '用户注册') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php 
    use app\services\ThemeManager;
    use app\config\FrontendConfig;
    $themeManager = new ThemeManager();
    $activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
    $themeBasePath = FrontendConfig::getThemePath($activeThemeId);
    $staticJsPath = FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS;
    // 注册页样式统一走当前启用的前台主题包
    $registerCssUrl = FrontendConfig::getThemeCssUrl('style.css', $activeThemeId, FrontendConfig::CACHE_VERSION);
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($registerCssUrl) ?>">
</head>
<body class="page-register">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="auth-brand-inner">
                <div class="brand-logo">
                    <?php if (!empty($siteLogoForAuth)): ?>
                        <img src="<?= htmlspecialchars($siteLogoForAuth) ?>" alt="<?= htmlspecialchars($siteNameForAuth) ?>">
                    <?php endif; ?>
                    <span class="brand-name"><?= htmlspecialchars($siteNameForAuth ?: '星夜阁') ?></span>
                </div>
                <h2 class="brand-headline">加入创作社区</h2>
                <p class="brand-description">与万千创作者一起，用AI释放无限创意</p>
                <div class="brand-features">
                    <div class="feature-item">
                        <div class="feature-icon">📝</div>
                        <div class="feature-text">AI小说创作</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🎵</div>
                        <div class="feature-text">AI音乐生成</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🎬</div>
                        <div class="feature-text">动画制作</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-form-section">
            <div class="auth-form-container">
                <div class="auth-header">
                    <h1 class="auth-title">创建账号</h1>
                    <p class="auth-subtitle">填写以下信息，开启创作之旅</p>
                </div>

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

                <?php if (!empty($success)): ?>
                <div class="auth-alert auth-alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span><?= htmlspecialchars((string) $success) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($showTabs): ?>
                <div class="register-tabs">
                    <button type="button" class="register-tab <?= $defaultTab === 'email' ? 'active' : '' ?>" data-tab="email">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span>邮箱注册</span>
                    </button>
                    <button type="button" class="register-tab <?= $defaultTab === 'phone' ? 'active' : '' ?>" data-tab="phone">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                        <span>手机号注册</span>
                    </button>
                </div>
                <?php endif; ?>

                <form class="auth-form" method="post" action="<?= htmlspecialchars((string) ($action ?? '/register')) ?>">
                    <input type="hidden" name="register_method" id="register_method" value="<?= htmlspecialchars($defaultTab) ?>">
                    
                    <div class="form-field">
                        <label for="username" class="field-label">用户名<span style="color:#f97373;"> *</span></label>
                        <input id="username" type="text" class="field-input" name="username" autocomplete="username" placeholder="请输入用户名" required autofocus>
                    </div>
                    
                    <?php if ($registerEmailEnabled): ?>
                    <div class="form-field register-email-field" style="<?= $showTabs && $defaultTab !== 'email' ? 'display:none;' : '' ?>">
                        <label for="email" class="field-label">邮箱<span style="color:#f97373;"> *</span></label>
                        <input id="email" type="email" class="field-input" name="email" autocomplete="email" placeholder="请输入邮箱地址" <?= $registerEmailEnabled && !$registerPhoneEnabled ? 'required' : '' ?>>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registerPhoneEnabled): ?>
                    <div class="form-field register-phone-field" style="<?= $showTabs && $defaultTab !== 'phone' ? 'display:none;' : '' ?>">
                        <label for="phone" class="field-label">手机号<span style="color:#f97373;"> *</span></label>
                        <input id="phone" type="tel" class="field-input" name="phone" autocomplete="tel" placeholder="请输入11位手机号" <?= $registerPhoneEnabled && !$registerEmailEnabled ? 'required' : '' ?>>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="password" class="field-label">密码<span style="color:#f97373;"> *</span></label>
                            <input id="password" type="password" class="field-input" name="password" autocomplete="new-password" placeholder="请设置密码" required>
                        </div>
                        <div class="form-field">
                            <label for="password_confirm" class="field-label">确认密码<span style="color:#f97373;"> *</span></label>
                            <input id="password_confirm" type="password" class="field-input" name="password_confirm" autocomplete="new-password" placeholder="请再次输入密码" required>
                        </div>
                    </div>
                    
                    <?php if (!empty($captcha_html ?? '')): ?>
                    <div class="form-field">
                        <?= $captcha_html ?>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="auth-submit">
                        <span>注册</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <div class="auth-footer">
                    <span>已有账号？</span>
                    <a href="/login">立即登录</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($showTabs): ?>
    <script src="<?= htmlspecialchars($staticJsPath) ?>/register.js"></script>
    <?php endif; ?>
</body>
</html>
