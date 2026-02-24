<?php
$siteNameForAuth = (string)($siteName ?? '');
$siteLogoForAuth = (string)($site_logo ?? '');
$token = (string)($token ?? '');
$hasToken = !empty($token);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '重置密码') ?></title>
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
    ?>
    <!-- 主题样式 - 从静态资源目录加载 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/style.css')) ?>">
</head>
<body>
    <div class="reset-wrapper">
        <div class="reset-card">
            <div class="reset-inner">
                <div class="reset-header">
                    <div class="reset-logo">
                        <?php if (!empty($siteLogoForAuth)): ?>
                            <img src="<?= htmlspecialchars($siteLogoForAuth) ?>" alt="<?= htmlspecialchars($siteNameForAuth) ?>">
                        <?php endif; ?>
                    </div>
                    <h1 class="reset-title">重置密码</h1>
                    <p class="reset-subtitle">
                        <?php if ($hasToken): ?>
                            输入收到的验证码，并设置一个新的安全密码
                        <?php else: ?>
                            请输入账号信息，我们将发送验证码到您的邮箱或手机
                        <?php endif; ?>
                    </p>
                </div>

                <div class="step-indicator">
                    <div class="step-item <?= !$hasToken ? 'active' : 'completed' ?>">
                        <span class="step-number">1</span>
                        <span>验证身份</span>
                    </div>
                    <div class="step-separator"></div>
                    <div class="step-item <?= $hasToken ? 'active' : '' ?>">
                        <span class="step-number">2</span>
                        <span>重置密码</span>
                    </div>
                </div>

                <?php if (!empty($error ?? '')): ?>
                    <div class="error-message">
                        <?= htmlspecialchars((string)$error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($message ?? '')): ?>
                    <div class="success-message">
                        <?= htmlspecialchars((string)$message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$hasToken): ?>
                    <!-- 第一步：输入账号信息并发送验证码 -->
                    <form class="reset-form" id="reset-step1-form">
                        <div class="form-group">
                            <label for="identifier" class="form-label">账号 / 邮箱 / 手机号<span style="color:#f97373;"> *</span></label>
                            <input id="identifier" type="text" class="form-control" name="identifier" placeholder="请输入登录账号、邮箱或手机号" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="method" class="form-label">验证方式<span style="color:#f97373;"> *</span></label>
                            <select id="method" name="method" class="form-control">
                                <option value="email">邮箱验证</option>
                                <option value="sms">短信验证</option>
                            </select>
                        </div>

                        <?php if (!empty($captcha_html ?? '')): ?>
                        <div class="form-group captcha-group">
                            <label class="form-label">安全验证<span style="color:#f97373;"> *</span></label>
                            <div class="captcha-inner">
                                <?= $captcha_html ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div id="reset-code-message" style="font-size: 0.85rem; text-align: center; margin-top: 8px; min-height: 20px;"></div>
                        <button type="button" id="send-reset-code-btn" class="btn-submit">发送验证码</button>
                    </form>
                <?php else: ?>
                    <!-- 第二步：输入验证码和新密码 -->
                    <form class="reset-form" method="post" action="/reset-password">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group">
                            <label for="code" class="form-label">验证码<span style="color:#f97373;"> *</span></label>
                            <input id="code" type="text" class="form-control" name="code" placeholder="请输入短信或邮件中的验证码" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">新密码<span style="color:#f97373;"> *</span></label>
                            <input id="password" type="password" class="form-control" name="password" placeholder="请输入新密码" required>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label">确认新密码<span style="color:#f97373;"> *</span></label>
                            <input id="password_confirm" type="password" class="form-control" name="password_confirm" placeholder="请再次输入新密码" required>
                        </div>

                        <button type="submit" class="btn-submit">重置密码</button>
                    </form>
                <?php endif; ?>

                <div class="reset-footer">
                    <a href="/login">返回登录</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!$hasToken): ?>
    <script src="<?= htmlspecialchars($staticJsPath) ?>/reset-password.js"></script>
    <?php endif; ?>
</body>
</html>
