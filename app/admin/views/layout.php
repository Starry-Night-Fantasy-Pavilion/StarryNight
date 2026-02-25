<?php
// 使用全局辅助函数获取资源版本
$assetVersion = asset_version('/static/admin/css/style.css');
// 为专用页面样式单独生成版本号，避免浏览器缓存问题
$crmCssVersion = asset_version('/static/admin/css/crm-users.css');
$modalCssVersion = asset_version('/static/admin/css/modal.css');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?= htmlspecialchars($title ?? '仪表盘') ?></title>
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json?v=<?= $assetVersion ?>">
    <meta name="theme-color" content="#6366f1">
    <!-- 基础样式 - 使用文件修改时间作为版本号 -->
    <link rel="stylesheet" href="/static/admin/css/style.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/responsive-tables.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/responsive-forms.css?v=<?= $assetVersion ?>">
    <?php if (strpos((string)$currentPage, 'content-review') === 0 || $currentPage === 'ai-audits'): ?>
    <?php $contentReviewCssVersion = asset_version('/static/admin/css/content-review.css'); ?>
    <link rel="stylesheet" href="/static/admin/css/content-review.css?v=<?= $contentReviewCssVersion ?>">
    <?php endif; ?>
    <?php if (strpos((string)$currentPage, 'ai-') === 0): ?>
    <link rel="stylesheet" href="/static/admin/css/ai-resources.css?v=<?= $assetVersion ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'future-features'): ?>
    <link rel="stylesheet" href="/static/admin/css/future-features.css?v=<?= $assetVersion ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'crm-users'): ?>
    <!-- CRM 用户管理专用样式 -->
    <link rel="stylesheet" href="/static/admin/css/crm-users.css?v=<?= $crmCssVersion ?>">
    <!-- 弹窗组件样式 -->
    <link rel="stylesheet" href="/static/admin/css/modal.css?v=<?= $modalCssVersion ?>">
    <?php endif; ?>
    <?php if ($currentPage === 'dashboard' || $currentPage === 'operations' || strpos((string)$currentPage, 'content-review') === 0 || strpos((string)$currentPage, 'finance') === 0 || strpos((string)$currentPage, 'system') === 0 || strpos((string)$currentPage, 'notice') === 0 || strpos((string)$currentPage, 'feedback') === 0 || strpos((string)$currentPage, 'announcement') === 0 || strpos((string)$currentPage, 'consistency') === 0): ?>
    <!-- 模块化CSS文件 -->
    <link rel="stylesheet" href="/static/admin/css/dashboard-base.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-cards.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-charts.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-icons.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-sections.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-forms.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-operations.css?v=<?= $assetVersion ?>">
    <link rel="stylesheet" href="/static/admin/css/dashboard-v2-cards.css?v=<?= $assetVersion ?>">
    <?php endif; ?>
    <!-- 优先使用 CDN Chart.js，如失败可在需要时再切换为本地版本 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <?php 
    $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
    use app\models\Setting;
    try {
        $siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
        $siteLogo = Setting::get('site_logo') ?: '/static/logo/logo.png';
    } catch (\Throwable $e) {
        error_log('Layout Setting::get() error: ' . $e->getMessage());
        $siteName = (string)get_env('APP_NAME', '星夜阁');
        $siteLogo = '/static/logo/logo.png';
    }
    ?>
    <div class="sidebar-overlay"></div>
    <div class="sidebar">
        <a class="sidebar-brand" href="/<?= $adminPrefix ?>">
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="sidebar-logo-img">
            <span class="sidebar-brand-name"><?= htmlspecialchars($siteName) ?></span>
        </a>

        <div class="sidebar-menu-wrapper">
            <div class="sidebar-toggle">
                <span class="toggle-icon"></span>
            </div>
            <div class="sidebar-menu-card">
                <nav class="sidebar-nav">
                <ul class="nav-group">
                    <li><a href="/<?= $adminPrefix ?>" class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>" title="运营仪表盘"><?= icon('dashboard') ?><span class="nav-text">运营仪表盘</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/crm/users" class="<?= ($currentPage === 'crm-users') ? 'active' : '' ?>" title="用户管理"><?= icon('users') ?><span class="nav-text">用户管理</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/finance/membership-levels" class="<?= (strpos((string)$currentPage, 'finance-') === 0 && $currentPage !== 'finance-templates') ? 'active' : '' ?>" title="会员、财务与营销管理"><?= icon('users') ?><span class="nav-text">会员与营销</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/finance/notification-templates" class="<?= ($currentPage === 'finance-templates') ? 'active' : '' ?>" title="短信/邮件模板"><?= icon('mail') ?><span class="nav-text">通知模板</span></a></li>
                </ul>
                <ul class="nav-group">
                    <li><a href="/<?= $adminPrefix ?>/content-review" class="<?= ($currentPage === 'content-review') ? 'active' : '' ?>" title="内容审查"><?= icon('book') ?><span class="nav-text">内容审查</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/community" class="<?= ($currentPage === 'community' || strpos((string)$currentPage, 'community-') === 0) ? 'active' : '' ?>" title="社区内容管理"><?= icon('activity') ?><span class="nav-text">社区内容</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/notice/list" class="<?= (strpos((string)$currentPage, 'notice') === 0 || strpos((string)$currentPage, 'feedback') === 0 || strpos((string)$currentPage, 'announcement') === 0) ? 'active' : '' ?>" title="通知栏、用户反馈、公告管理"><?= icon('activity') ?><span class="nav-text">通知与公告</span></a></li>
                </ul>
                <ul class="nav-group">
                    <li><a href="/<?= $adminPrefix ?>/ai/channels" class="<?= (strpos((string)$currentPage, 'ai-') === 0) ? 'active' : '' ?>" title="AI 资源与配置管理"><?= icon('plugins') ?><span class="nav-text">AI资源配置</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/knowledge" class="<?= ($currentPage === 'knowledge' || strpos((string)$currentPage, 'knowledge-') === 0) ? 'active' : '' ?>" title="知识库与提示词管理"><?= icon('book') ?><span class="nav-text">知识库管理</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/future-features" class="<?= ($currentPage === 'future-features') ? 'active' : '' ?>" title="未来功能管理"><?= icon('activity') ?><span class="nav-text">未来功能</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/plugins" class="<?= ($currentPage === 'plugins') ? 'active' : '' ?>" title="插件管理"><?= icon('plugins') ?><span class="nav-text">插件管理</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/themes" class="<?= ($currentPage === 'themes') ? 'active' : '' ?>" title="主题管理"><?= icon('themes') ?><span class="nav-text">主题管理</span></a></li>
                </ul>
                <ul class="nav-group nav-group-system">
                    <li><a href="/<?= $adminPrefix ?>/consistency/config" class="<?= (strpos((string)$currentPage, 'consistency') === 0) ? 'active' : '' ?>" title="一致性检查系统"><?= icon('check-circle') ?><span class="nav-text">一致性检查</span></a></li>
                    <li><a href="/<?= $adminPrefix ?>/system/basic-settings" class="<?= (strpos((string)$currentPage, 'system') === 0) ? 'active' : '' ?>" title="系统配置与安全审计"><?= icon('book') ?><span class="nav-text">系统配置</span></a></li>
                </ul>
                </nav>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-left">
                <div class="sidebar-user-avatar"></div>
                <div class="sidebar-user-meta">
                    <div class="sidebar-user-name">管理员</div>
                    <div class="sidebar-user-status">在线</div>
                </div>
            </div>
            <a class="sidebar-logout" href="/<?= $adminPrefix ?>/logout" title="退出登录">
                <?= icon('logout') ?>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="breadcrumb">
                <a href="/<?= $adminPrefix ?>">后台管理</a>
                <span>/</span>
                <a href="#"><?= htmlspecialchars($title ?? '仪表盘') ?></a>
            </div>
            <div class="user-actions">
                <a href="/<?= $adminPrefix ?>/notifications" class="icon-btn" title="通知">
                    <?= icon('bell', ['width' => '20', 'height' => '20']) ?>
                </a>
                <a href="/<?= $adminPrefix ?>/profile" class="icon-btn" title="个人资料">
                    <?= icon('user', ['width' => '20', 'height' => '20']) ?>
                </a>
            </div>
        </div>
        <div class="main-content-wrapper">
            <?= $content ?? '' ?>
        </div>
    </div>

    <?php if ($currentPage === 'dashboard' || $currentPage === 'operations'): ?>
    <!-- 模块化JS文件 -->
    <script src="/static/admin/js/dashboard-charts.js?v=<?= $assetVersion ?>"></script>
    <script src="/static/admin/js/dashboard-forms.js?v=<?= $assetVersion ?>"></script>
    <script src="/static/admin/js/dashboard-interactions.js?v=<?= $assetVersion ?>"></script>
    <?php endif; ?>
    <script>
        window.ADMIN_PREFIX = <?= json_encode($adminPrefix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <!-- 延迟加载非关键JS -->
    <script src="/static/admin/js/admin-forms.js?v=<?= $assetVersion ?>" defer></script>
    <script src="/static/admin/js/sidebar-toggle.js?v=<?= $assetVersion ?>" defer></script>
    <script src="/static/admin/js/responsive-tables.js?v=<?= $assetVersion ?>" defer></script>
    <script src="/static/admin/js/responsive-forms.js?v=<?= $assetVersion ?>" defer></script>
    <script src="/static/admin/js/touch-interactions.js?v=<?= $assetVersion ?>" defer></script>
    <script src="/static/admin/js/export-functionality.js?v=<?= $assetVersion ?>" defer></script>
    <?php if ($currentPage === 'operations'): ?>
    <script src="/static/admin/js/operations-charts.js?v=<?= $assetVersion ?>" defer></script>
    <?php endif; ?>
    <?php if ($currentPage === 'future-features'): ?>
    <script src="/static/admin/js/future-features.js?v=<?= $assetVersion ?>" defer></script>
    <?php endif; ?>
    
    <!-- 弹窗组件脚本 (CRM用户管理页面) -->
    <?php if ($currentPage === 'crm-users'): ?>
    <script src="/static/admin/js/modal.js?v=<?= time() ?>" defer></script>
    <script src="/static/admin/js/crm-users.js?v=<?= time() ?>" defer></script>
    <?php endif; ?>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registered:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
