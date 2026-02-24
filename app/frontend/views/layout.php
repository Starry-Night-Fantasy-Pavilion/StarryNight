<?php
use app\models\Setting;
use app\services\ThemeManager;
use app\config\FrontendConfig;

try {
    $siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
    $siteLogo = Setting::get('site_logo');
} catch (\Exception $e) {
    // 如果Setting表不存在或查询失败，使用默认值
    $siteName = (string)get_env('APP_NAME', '星夜阁');
    $siteLogo = null;
}

// 获取当前主题路径
$themeManager = new ThemeManager();
$activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
$themeBasePath = FrontendConfig::getThemePath($activeThemeId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? $siteName)) ?></title>
    
    <!-- 主题样式 - 从静态资源目录加载 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/style.css')) ?>">
    
    <!-- 额外CSS -->
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <?php if (str_starts_with($css, '/')): ?>
                <!-- 绝对路径 -->
                <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
            <?php else: ?>
                <!-- 相对路径，从静态资源目录加载 -->
                <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/' . $css)) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div>
            <a href="/" class="header-logo">
                <?php if ($siteLogo): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                <?php endif; ?>
                <span><?= htmlspecialchars($siteName) ?></span>
            </a>
        </div>
        <nav class="header-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/novel_creation">小说创作</a>
                <a href="/ai_music">AI音乐</a>
                <a href="/anime_production">动漫制作</a>
                <a href="/knowledge">知识库</a>
                <a href="/templates">模板库</a>
                <a href="/agents">智能体</a>
                <a href="/share">资源分享</a>
                <a href="/ranking">排行榜</a>
                <a href="/user_center">用户中心</a>
                <a href="/logout">退出</a>
            <?php else: ?>
                <a href="/novel_creation">小说创作</a>
                <a href="/ai_music">AI音乐</a>
                <a href="/anime_production">动漫制作</a>
                <a href="/knowledge">知识库</a>
                <a href="/templates">模板库</a>
                <a href="/agents">智能体</a>
                <a href="/share">资源分享</a>
                <a href="/ranking">排行榜</a>
                <a href="/login">登录</a>
                <a href="/register">注册</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <main class="main-content">
        <?= $content ?? '' ?>
    </main>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    
    <!-- 主题脚本 - 从静态资源目录加载 -->
    <script src="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/theme.js')) ?>"></script>
    
    <!-- Service Worker 注册 -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('Service Worker registered successfully:', registration.scope);
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        });
    }
    </script>
    
    <!-- 额外JS -->
    <?php if (isset($extra_js) && is_array($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?= htmlspecialchars($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
