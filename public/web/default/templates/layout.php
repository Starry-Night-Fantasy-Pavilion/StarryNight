<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? '星夜阁')) ?></title>
.    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars((string) ($meta_description ?? '星夜阁 - 专为创作者打造的AI智能创作平台，提供AI小说写作、音乐生成、动画制作等功能，助力创作者提升效率10倍以上')) ?>">
    <meta name="keywords" content="<?= htmlspecialchars((string) ($meta_keywords ?? 'AI写作,AI创作,小说创作,AI小说,智能写作,创作工具,写作助手,星夜阁')) ?>">
    <meta name="author" content="<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'])) ?>">
    <meta property="og:title" content="<?= htmlspecialchars((string) ($title ?? '星夜阁 - AI智能创作平台')) ?>">
    <meta property="og:description" content="<?= htmlspecialchars((string) ($meta_description ?? '专为创作者打造的AI智能创作平台，提供AI小说写作、音乐生成、动画制作等功能')) ?>">
    <meta property="og:image" content="<?= htmlspecialchars((string) ($og_image ?? \app\config\FrontendConfig::getThemeImageUrl('og-image.png'))) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>">
    <meta property="og:locale" content="zh_CN">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'])) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars((string) ($title ?? '星夜阁 - AI智能创作平台')) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars((string) ($meta_description ?? '专为创作者打造的AI智能创作平台')) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars((string) ($og_image ?? \app\config\FrontendConfig::getThemeImageUrl('og-image.png'))) ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'])) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php
    use app\config\FrontendConfig;
    $isAuthPage = isset($page_class) && ($page_class === 'page-login' || $page_class === 'page-register');
    // 登录页背景图片使用主题图片路径，避免硬编码
    $loginBgImage = FrontendConfig::getThemeImageUrl('IMG_20260217_233007.jpg');
    ?>
    
    <?php if ($isAuthPage): ?>
        <!-- 主题包共享登录样式 -->
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/login.css')) ?>">
        <!-- 登录页面自定义样式 - 放在外部CSS之后以确保优先级 -->
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
    --admin-login-radius-lg: 20px;
    --admin-login-radius-md: 10px;
    --admin-login-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
}

body.page-login {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0; /* 去掉外层留白，让登录容器再"放大"一圈 */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI",
        Roboto, "Noto Sans SC", sans-serif;
    /* 使用主题图片作为背景 */
    background-image: url('<?= htmlspecialchars($loginBgImage, ENT_QUOTES) ?>');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: var(--admin-login-text);
    /* 隐藏页面滚动条，登录卡片内内容全部收紧显示 */
    overflow: hidden;
}

body.page-login * {
    box-sizing: border-box;
}

.login-wrapper {
    width: 100%;
    max-width: 1280px !important; /* 再放大一档，整体视觉更宽 */
    margin: 0 auto;
}

.login-card {
    width: 100%;
    max-width: 100% !important;
    background: var(--admin-login-card-bg);
    backdrop-filter: blur(18px) saturate(150%);
    -webkit-backdrop-filter: blur(18px) saturate(150%);
    border-radius: var(--admin-login-radius-lg);
    border: 1px solid var(--admin-login-border);
    box-shadow: var(--admin-login-shadow);
    padding: 46px 80px 40px !important; /* 再加大内边距，让卡片整体明显变大 */
}

.login-inner {
    width: 100%;
    max-width: 100%;
}

/* 桌面端：登录表单采用两列排版，更好占满空间，整体显得更"大" */
@media (min-width: 900px) {
    .login-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 28px;
        row-gap: 16px;
    }

    .login-form .form-group {
        margin: 0;
    }

    /* 验证码和登录按钮占满两列，形成一整行大区域 */
    .login-form .captcha-group,
    .login-form .btn-login {
        grid-column: 1 / -1;
    }
}

.login-header {
    text-align: center;
    margin-bottom: 24px; /* 增加头部与表单的间距 */
}

.login-logo {
    margin-bottom: 14px;
    display: flex;
    justify-content: center;
}

.login-logo img {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    object-fit: contain;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.55);
}

.login-title {
    font-size: 2.4rem !important; /* 标题明显变大，肉眼可见 */
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    background: linear-gradient(135deg, var(--admin-login-primary), #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.login-subtitle {
    font-size: 0.92rem;
    color: var(--admin-login-text-muted);
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px; /* 稍微增加间距，让布局更舒适 */
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px; /* 标签和输入框之间稍微增加间距 */
}

.form-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
}

.form-control {
    width: 100%;
    padding: 12px 16px !important; /* 适中的输入框尺寸 */
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: var(--admin-login-input-bg);
    color: var(--admin-login-text);
    font-size: 1rem !important; /* 标准字体大小 */
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
    padding: 10px 12px;
    border-radius: var(--admin-login-radius-md);
    font-size: 0.85rem;
    margin-bottom: 10px;
    text-align: center;
}

.btn-login {
    width: 100%;
    margin-top: 4px !important; /* 减少顶部间距，与验证码区域更紧凑 */
    padding: 14px 20px !important; /* 按钮更高更大 */
    border-radius: var(--admin-login-radius-md);
    border: none;
    cursor: pointer;
    font-size: 1.06rem !important;
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

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(59, 130, 246, 0.75);
    filter: brightness(1.03);
}

.btn-login:active {
    transform: translateY(0);
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.5);
}

.login-footer {
    margin-top: 20px; /* 增加底部间距，让布局更舒适 */
    text-align: center;
    font-size: 0.85rem; /* 稍微增大字体 */
    color: var(--admin-login-text-muted);
}

.login-footer a {
    color: #fff;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.login-legal {
    margin-top: 12px;
    text-align: center;
    font-size: 1.02rem;
    line-height: 1.7;
    color: #fca5a5;
    font-weight: 600;
}

.login-legal span {
    margin-right: 4px;
}

.login-legal a {
    color: #fecaca;
    text-decoration: none;
    font-weight: 700;
}

.login-legal a:hover {
    text-decoration: underline;
}

.third-party-block {
    margin-top: 16px;
    text-align: center;
}

.third-party-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--admin-login-text);
    margin-bottom: 8px;
}

.third-party-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.third-party-buttons a,
.third-party-buttons button {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(0, 0, 0, 0.35);
    color: #fff;
    overflow: hidden;
}

.third-party-buttons img,
.third-party-buttons svg {
    width: 20px;
    height: 20px;
}

.link-forgot {
    font-size: 0.78rem;
    color: var(--admin-login-text-muted);
    text-decoration: none;
}

.link-forgot:hover {
    text-decoration: underline;
}

.login-tabs {
    display: flex;
    justify-content: flex-start;
    gap: 12px;
    margin-bottom: 20px;
}

.login-tab {
    padding: 6px 14px;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.6);
    background: #ffffff;
    color: #0f172a;
    font-size: 13px;
    cursor: default;
}

.login-tab.active {
    border-color: #22c55e;
    background: #ecfdf3;
    color: #16a34a;
}

/* 验证码区域：标签在外面，和账号/密码一样的结构 */
.captcha-group {
    /* 使用 form-group 的默认样式，标签在外面，不需要额外的 padding/border/background */
}

.captcha-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

/* 验证码组件正常大小，不再缩小 */
.captcha-inner > * {
    max-width: 100%;
}

/* 覆盖基础验证码插件样式：改成"整行输入框 + 题目" */
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

/* 不要图片，只保留输入框和问题文字 */
.captcha-group .captcha-image {
    display: none !important;
}

/* 输入框占满整行，题目在右侧，样式与账号/密码输入框完全一致 */
.captcha-group .captcha-input {
    order: 1;
    flex: 1; /* 输入框占满剩余空间 */
    width: 100%;
    padding: 12px 16px !important; /* 与表单输入框完全一致 */
    font-size: 1rem !important; /* 与表单输入框完全一致 */
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
    padding: 12px 16px; /* 与输入框内边距一致 */
    font-size: 1rem; /* 与输入框字体一致 */
    color: rgba(255, 255, 255, 0.85);
    white-space: nowrap;
    font-weight: 500;
    flex-shrink: 0; /* 题目不收缩 */
    /* 单独框子样式 */
    border-radius: var(--admin-login-radius-md);
    border: 1px solid var(--admin-login-border);
    background: rgba(0, 0, 0, 0.35);
    text-align: center;
    min-width: 110px; /* 适中的最小宽度 */
}

@media (max-width: 480px) {
    body.page-login {
        padding: 16px;
    }

    .login-card {
        padding: 26px 22px 22px !important;
    }

    .login-title {
        font-size: 1.6rem !important;
    }
}

/* 法律信息弹窗样式 */
.auth-legal-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    pointer-events: none;
}

.auth-legal-modal.visible {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.auth-legal-modal-dialog {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    background: rgba(26, 26, 46, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.auth-legal-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.03);
}

.auth-legal-modal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    letter-spacing: 0.02em;
}

.auth-legal-modal-close {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0;
    flex-shrink: 0;
}

.auth-legal-modal-close svg {
    width: 20px;
    height: 20px;
}

.auth-legal-modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.4);
    color: #fca5a5;
    transform: scale(1.05);
}

.auth-legal-modal-close:active {
    transform: scale(0.95);
}

.auth-legal-modal-body {
    padding: 24px 32px;
    overflow-y: auto;
    flex: 1;
}

.auth-legal-modal-content {
    margin: 0;
    font-size: 1rem;
    line-height: 1.8;
    color: rgba(255, 255, 255, 0.9);
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans SC", sans-serif;
}

.auth-legal-modal-content:empty::before {
    content: '加载中...';
    color: rgba(255, 255, 255, 0.5);
    font-size: 1rem;
}

@media (max-width: 768px) {
    .auth-legal-modal {
        padding: 20px;
    }
    
    .auth-legal-modal-dialog {
        max-width: 100%;
        max-height: 95vh;
    }
    
    .auth-legal-modal-header {
        padding: 20px 24px;
    }
    
    .auth-legal-modal-title {
        font-size: 1.5rem;
    }
    
    .auth-legal-modal-body {
        padding: 20px 24px;
    }
}
        </style>
    <?php else: ?>
        <!-- 主题包共享管理样式 -->
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/style.css')) ?>">
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-tables.css')) ?>">
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-forms.css')) ?>">
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-base.css')) ?>">
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-v2-cards.css')) ?>">
    <?php endif; ?>
    
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- 基础样式 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('base.css')) ?>">
    
    <!-- 首页专用样式 -->
    <?php if (isset($current_page) && $current_page === 'home'): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('pages/home.css')) ?>">
    <?php endif; ?>
    
    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>",
        "url": "<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) ?>",
        "description": "<?= htmlspecialchars((string) ($meta_description ?? '专为创作者打造的AI智能创作平台')) ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>",
        "url": "<?= htmlspecialchars((string) ($canonical_url ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'))) ?>",
        "logo": "<?= htmlspecialchars((string) ($site_logo ?? FrontendConfig::getThemeImageUrl('logo.png'))) ?>",
        "sameAs": [
            "https://weibo.com/starrynight",
            "https://github.com/starrynight"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+86-400-888-8888",
            "contactType": "customer service",
            "availableLanguage": ["Chinese", "English"]
        }
    }
    </script>
    
    <?php if (isset($current_page) && $current_page === 'home'): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>",
        "applicationCategory": "CreativeApplication",
        "operatingSystem": "Web",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "CNY"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "10000"
        }
    }
    </script>
    <?php endif; ?>
</head>
<body class="<?= isset($page_class) ? htmlspecialchars($page_class) : '' ?><?= $isAuthPage ? ' admin-login-page' : '' ?>">
    <?php 
    $isAuthPage = isset($page_class) && ($page_class === 'page-login' || $page_class === 'page-register');
    ?>
    
    <?php 
    // 首页有自己的导航栏，其他页面显示默认header
    $isHomePage = isset($current_page) && $current_page === 'home';
    ?>
    <?php if (!$isAuthPage && !$isHomePage): ?>
    <header class="header" id="header">
        <div class="header-brand">
            <a href="/" class="header-logo">
                <?php if (!empty($site_logo)): ?>
                    <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>" class="header-logo-img">
                <?php endif; ?>
                <span><?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?></span>
            </a>
        </div>
        <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="菜单">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="header-nav" id="header-nav">
            <ul class="nav-menu">
                <li><a href="/" class="<?= (isset($current_page) && $current_page === 'home') ? 'active' : '' ?>">首页</a></li>
                <li><a href="/novel_creation" class="<?= (isset($current_page) && $current_page === 'novel_creation') ? 'active' : '' ?>">小说创作</a></li>
                <li><a href="/ai_music" class="<?= (isset($current_page) && $current_page === 'ai_music') ? 'active' : '' ?>">AI音乐</a></li>
                <li><a href="/anime_production" class="<?= (isset($current_page) && $current_page === 'anime_production') ? 'active' : '' ?>">动画制作</a></li>
                <li><a href="/knowledge" class="<?= (isset($current_page) && $current_page === 'knowledge') ? 'active' : '' ?>">知识库</a></li>
                <li><a href="/templates" class="<?= (isset($current_page) && $current_page === 'templates') ? 'active' : '' ?>">模板库</a></li>
                <li><a href="/agents" class="<?= (isset($current_page) && $current_page === 'agents') ? 'active' : '' ?>">智能体</a></li>
                <li><a href="/ranking" class="<?= (isset($current_page) && $current_page === 'ranking') ? 'active' : '' ?>">排行榜</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <div class="language-switcher" style="display: inline-block; margin-right: 10px;">
                <select id="language-select" style="padding: 6px 12px; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.9); cursor: pointer; font-size: 14px;">
                    <option value="zh-cn" <?= (($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-cn') === 'zh-cn') ? 'selected' : '' ?>>中文</option>
                    <option value="en" <?= (($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-cn') === 'en') ? 'selected' : '' ?>>English</option>
                </select>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/user_center" class="btn btn-secondary btn-sm">个人中心</a>
                <a href="/logout" class="btn btn-ghost btn-sm">退出</a>
            <?php else: ?>
                <a href="/login" class="btn btn-ghost btn-sm">登录</a>
                <a href="/register" class="btn btn-primary btn-sm">免费注册</a>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>

    <main class="main<?= $isAuthPage ? ' main-auth' : '' ?>">
        <?= $content ?? '' ?>
    </main>

    <?php 
    // 首页有自己的页脚，其他页面显示默认页脚
    $isHomePage = isset($current_page) && $current_page === 'home';
    ?>
    <?php if (!$isAuthPage && !$isHomePage): ?>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-brand">
                    <?php if (!empty($site_logo)): ?>
                        <img src="<?= htmlspecialchars($site_logo) ?>" alt="<?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>" class="footer-logo-img">
                    <?php endif; ?>
                    <span class="footer-brand-name"><?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?></span>
                </div>
                <p>专为网络小说、剧本创作者打造的AI增效工具。辅助10000+作者在番茄起点等网站写作，让创作更高效、更有灵感。</p>
                <div class="footer-social">
                    <a href="#" title="微信公众号">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.582.582 0 0 1-.023-.156.49.49 0 0 1 .201-.398C23.024 18.48 24 16.82 24 14.98c0-3.21-2.931-5.837-6.656-6.088V8.89c-.135-.01-.27-.027-.407-.03zm-2.53 3.274c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.97-.982zm4.844 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.983.976.976 0 0 1-.969-.983c0-.542.434-.982.969-.982z"/>
                        </svg>
                    </a>
                    <a href="#" title="微博">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10.098 20.323c-3.977.391-7.414-1.406-7.672-4.02-.259-2.609 2.759-5.047 6.74-5.441 3.979-.394 7.413 1.404 7.671 4.018.259 2.6-2.759 5.049-6.739 5.443zM9.05 17.219c-.384.616-1.208.884-1.829.602-.612-.279-.793-.991-.406-1.593.379-.595 1.176-.861 1.793-.601.622.263.82.972.442 1.592zm1.27-1.627c-.141.237-.449.353-.689.253-.236-.09-.313-.361-.177-.586.138-.227.436-.346.672-.24.239.09.315.36.194.573zm.176-2.719c-1.893-.493-4.033.45-4.857 2.118-.836 1.704-.026 3.591 1.886 4.21 1.983.64 4.318-.341 5.132-2.179.8-1.793-.201-3.642-2.161-4.149zm7.563-1.224c-.346-.105-.579-.18-.405-.649.388-1.032.428-1.922.006-2.556-.786-1.18-2.936-1.116-5.381-.034 0 0-.77.337-.573-.274.381-1.217.324-2.236-.27-2.823-1.349-1.336-4.938-.058-8.019 2.853C1.096 10.69 0 12.992 0 14.982c0 3.813 4.892 6.134 9.675 6.134 6.268 0 10.436-3.639 10.436-6.532 0-1.747-1.473-2.738-2.052-2.935zm1.168-4.729c-.637-.758-1.578-1.107-2.644-1.037l.003-.003c-.378.025-.642.34-.59.706.052.366.38.634.758.609.634-.042 1.174.146 1.549.59.376.446.503 1.024.383 1.685-.064.36.171.709.53.773.36.064.708-.17.772-.53.191-1.065-.014-2.018-.761-2.793zm2.328-2.712c-1.23-1.464-3.05-2.138-5.108-2.003-.387.026-.678.36-.649.744.029.385.362.675.749.649 1.574-.103 2.942.412 3.88 1.528.938 1.116 1.279 2.547.988 4.127-.07.38.181.746.56.816.38.07.746-.18.816-.56.381-2.078-.063-3.946-1.236-5.301z"/>
                        </svg>
                    </a>
                    <a href="#" title="GitHub">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>产品功能</h3>
                <ul>
                    <li><a href="/novel_creation">AI小说创作</a></li>
                    <li><a href="/ai_music">AI音乐生成</a></li>
                    <li><a href="/anime_production">动画制作</a></li>
                    <li><a href="/knowledge">知识库管理</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>支持与帮助</h3>
                <ul>
                    <li><a href="/announcement">平台公告</a></li>
                    <li><a href="/feedback">意见反馈</a></li>
                    <li><a href="/membership">会员中心</a></li>
                    <li><a href="/user_center">用户中心</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars((string) ($site_name ?? '星夜阁')) ?>. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">用户协议</a>
                <a href="#">隐私政策</a>
                <a href="#">关于我们</a>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <?php
    $themeBase = $theme_base_path ?? FrontendConfig::getThemePath(FrontendConfig::THEME_DEFAULT);
    ?>
    
    <?php if ($isAuthPage): ?>
    <!-- 法律信息弹窗 - 仅在登录/注册页面显示 -->
    <div id="auth-legal-modal" class="auth-legal-modal" aria-hidden="true" role="dialog" aria-labelledby="auth-legal-modal-title">
        <div class="auth-legal-modal-dialog">
            <div class="auth-legal-modal-header">
                <h2 id="auth-legal-modal-title" class="auth-legal-modal-title">用户协议</h2>
                <button type="button" class="auth-legal-modal-close" aria-label="关闭">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="auth-legal-modal-body">
                <div id="auth-legal-modal-content" class="auth-legal-modal-content">加载中...</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('theme.js')) ?>"></script>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('components/language-switcher.js')) ?>"></script>
    <?php if (isset($extra_js) && is_array($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?= htmlspecialchars($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
