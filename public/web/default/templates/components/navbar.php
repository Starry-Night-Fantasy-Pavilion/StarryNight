<?php
/**
 * 通用顶部导航栏组件（非官网首页、非登录注册页）
 *
 * 期望外部传入变量：
 * - $current_page: 当前页面标识（如 'novel_creation' / 'ai_music' ...）
 * - $site_name / $site_logo: 站点名称与logo
 * - $nav_items: 可选，自定义导航项
 */
 
$siteName = (string) ($site_name ?? '星夜阁');
$siteLogo = $site_logo ?? null;
$currentPage = (string) ($current_page ?? '');

$lang = (string) ($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-cn');
$isLoggedIn = isset($_SESSION['user_id']);

// 默认导航项（保持与旧 layout.php 一致）
$navItems = $nav_items ?? [
    ['key' => 'home',            'href' => '/',                'label' => '首页'],
    ['key' => 'novel_creation',  'href' => '/novel_creation',  'label' => '小说创作'],
    ['key' => 'ai_music',        'href' => '/ai_music',        'label' => 'AI音乐'],
    ['key' => 'anime_production','href' => '/anime_production','label' => '动画制作'],
    ['key' => 'knowledge',       'href' => '/knowledge',       'label' => '知识库'],
    ['key' => 'templates',       'href' => '/templates',       'label' => '模板库'],
    ['key' => 'agents',          'href' => '/agents',          'label' => '智能体'],
    ['key' => 'ranking',         'href' => '/ranking',         'label' => '排行榜'],
];
?>

<header class="header" id="header">
    <div class="header-brand">
        <a href="/" class="header-logo">
            <?php if (!empty($siteLogo)): ?>
                <img src="<?= htmlspecialchars((string)$siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="header-logo-img">
            <?php endif; ?>
            <span><?= htmlspecialchars($siteName) ?></span>
        </a>
    </div>

    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="菜单">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav class="header-nav" id="header-nav">
        <ul class="nav-menu">
            <?php foreach ($navItems as $item): ?>
                <?php
                    $key = (string) ($item['key'] ?? '');
                    $href = (string) ($item['href'] ?? '#');
                    $label = (string) ($item['label'] ?? '');
                    $active = $key !== '' ? ($currentPage === $key) : (parse_url($href, PHP_URL_PATH) === ($_SERVER['REQUEST_URI'] ?? ''));
                ?>
                <li>
                    <a href="<?= htmlspecialchars($href) ?>" class="<?= $active ? 'active' : '' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="header-actions">
        <?php if ($isLoggedIn): ?>
            <a href="/user_center" class="btn btn-secondary btn-sm">个人中心</a>
        <?php endif; ?>
    </div>
</header>

