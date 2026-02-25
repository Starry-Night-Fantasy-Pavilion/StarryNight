<?php
use app\models\Setting;
use app\services\ThemeManager;
use app\config\FrontendConfig;

try {
    $siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
    $siteLogo = Setting::get('site_logo');
} catch (\Exception $e) {
    $siteName = (string)get_env('APP_NAME', '星夜阁');
    $siteLogo = null;
}

$themeManager = new ThemeManager();
$activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
$themeBasePath = FrontendConfig::getThemePath($activeThemeId);

$currentPage = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6366f1">
    <title><?= htmlspecialchars((string) ($title ?? $siteName)) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <?php
    // 统一走前台主题包的样式入口，根据后台启用的主题加载 CSS
    $themeCssVersion = FrontendConfig::CACHE_VERSION;
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('style.css', $activeThemeId, $themeCssVersion)) ?>">
    
    <?php if (isset($extra_css) && is_array($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <!-- 这里假定 $extra_css 中已经是完整的 URL（例如通过 FrontendConfig::getThemeCssUrl 构造） -->
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <header class="header" id="mainHeader">
        <div class="header-brand">
            <a href="/" class="header-logo">
                <?php if ($siteLogo): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="header-logo-img">
                <?php endif; ?>
                <span><?= htmlspecialchars($siteName) ?></span>
            </a>
        </div>
        
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="菜单">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <nav class="header-nav" id="headerNav">
            <ul class="nav-menu">
                <li><a href="/novel_creation" <?= str_contains($currentPage, '/novel_creation') ? 'class="active"' : '' ?>>小说创作</a></li>
                <li><a href="/ai_music" <?= str_contains($currentPage, '/ai_music') ? 'class="active"' : '' ?>>AI音乐</a></li>
                <li><a href="/novel_creation/short_drama" <?= str_contains($currentPage, '/short_drama') ? 'class="active"' : '' ?>>短剧创作</a></li>
                <li><a href="/novel_creation/cover_generator" <?= str_contains($currentPage, '/cover_generator') ? 'class="active"' : '' ?>>图片生成</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/chat" class="icon-btn" title="对话">
                    <?= icon('message-circle', ['width' => '18', 'height' => '18']) ?>
                </a>
                <a href="/notifications" class="icon-btn" title="通知">
                    <?= icon('bell', ['width' => '18', 'height' => '18']) ?>
                </a>
                <a href="/user_center/profile" class="icon-btn" title="个人中心">
                    <?= icon('user', ['width' => '18', 'height' => '18']) ?>
                </a>
            <?php else: ?>
                <a href="/login" class="btn btn-ghost btn-sm">登录</a>
                <a href="/register" class="btn btn-primary btn-sm">注册</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="main">
        <?= $content ?? '' ?>
    </main>
    
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-brand">
                    <?php if ($siteLogo): ?>
                        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="footer-logo-img">
                    <?php endif; ?>
                    <span class="footer-brand-name"><?= htmlspecialchars($siteName) ?></span>
                </div>
                <p>探索AI创作的无限可能，让创意触手可及。我们致力于为创作者提供最先进的AI工具，助力每一个创意梦想。</p>
                <div class="footer-social">
                    <a href="#" aria-label="微信">
                        <?= icon('message-circle', ['width' => '18', 'height' => '18']) ?>
                    </a>
                    <a href="#" aria-label="微博">
                        <?= icon('at-sign', ['width' => '18', 'height' => '18']) ?>
                    </a>
                    <a href="#" aria-label="QQ">
                        <?= icon('message-square', ['width' => '18', 'height' => '18']) ?>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>创作功能</h3>
                <ul>
                    <li><a href="/novel_creation">小说创作</a></li>
                    <li><a href="/ai_music">AI音乐</a></li>
                    <li><a href="/novel_creation/short_drama">短剧创作</a></li>
                    <li><a href="/novel_creation/cover_generator">图片生成</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>帮助支持</h3>
                <ul>
                    <li><a href="/tutorial">使用教程</a></li>
                    <li><a href="/help">帮助中心</a></li>
                    <li><a href="/feedback">意见反馈</a></li>
                    <li><a href="/about">关于我们</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
            <div class="footer-links">
                <a href="/privacy">隐私政策</a>
                <a href="/terms">服务条款</a>
                <a href="/contact">联系我们</a>
            </div>
        </div>
    </footer>
    
    <script src="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/theme.js')) ?>"></script>
    
    <script>
    (function() {
        const header = document.getElementById('mainHeader');
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const headerNav = document.getElementById('headerNav');
        
        if (mobileToggle && headerNav) {
            mobileToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                headerNav.classList.toggle('active');
            });
        }
        
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            if (header) {
                if (currentScroll > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
            lastScroll = currentScroll;
        });
    })();
    
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
    
    <?php if (isset($extra_js) && is_array($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?= htmlspecialchars($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
