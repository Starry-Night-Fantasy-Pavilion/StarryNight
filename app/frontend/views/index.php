<?php
use app\models\Setting;
$siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');

// 检查当前登录状态（支持 Session + 30天自动登录）
$isLoggedIn = !empty($_SESSION['user_logged_in']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($siteName) ?> - 首页总览</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- 复用后台管理的样式体系，让前台首页风格接近后台界面 -->
    <link rel="stylesheet" href="/static/admin/css/style.css">
    <link rel="stylesheet" href="/static/admin/css/dashboard-base.css">
    <link rel="stylesheet" href="/static/admin/css/dashboard-v2-cards.css">
    <?php 
    use app\services\ThemeManager;
    use app\config\FrontendConfig;
    $themeManager = new ThemeManager();
    $activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
    $themeBasePath = FrontendConfig::getThemePath($activeThemeId);
    // 前台工作台首页也通过当前主题包的样式入口进行渲染
    $frontendCssUrl = FrontendConfig::getThemeCssUrl('style.css', $activeThemeId, FrontendConfig::CACHE_VERSION);
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($frontendCssUrl) ?>">
</head>
<body>
    <div class="app-layout">
        <!-- 这里不直接复刻后台完整侧边栏，只保留一个简化版首页导航 -->
        <aside class="sidebar" style="position: fixed;">
            <div class="sidebar-menu-wrapper">
                <a href="/" class="sidebar-brand">
                    <img src="/static/admin/img/logo.png" alt="<?= htmlspecialchars($siteName) ?>" class="sidebar-logo-img">
                    <span class="sidebar-brand-name"><?= htmlspecialchars($siteName) ?></span>
                </a>

                <div class="sidebar-menu-card">
                    <div class="menu-section">
                        <div class="menu-section-title">作品与创作</div>
                        <a href="/novel" class="menu-item">
                            <span class="menu-item-label">我的小说</span>
                        </a>
                        <a href="/novel" class="menu-item">
                            <span class="menu-item-label">小说创作工具</span>
                        </a>
                        <a href="/music/project/list" class="menu-item">
                            <span class="menu-item-label">AI音乐创作</span>
                        </a>
                        <a href="/anime/project/list" class="menu-item">
                            <span class="menu-item-label">动漫制作</span>
                        </a>
                    </div>

                    <div class="menu-section">
                        <div class="menu-section-title">AI辅助工具</div>
                        <a href="/novel_creation/editor" class="menu-item">
                            <span class="menu-item-label">智能编辑器</span>
                        </a>
                        <a href="/novel_creation/outline_generator" class="menu-item">
                            <span class="menu-item-label">大纲生成</span>
                        </a>
                        <a href="/novel_creation/character_manager" class="menu-item">
                            <span class="menu-item-label">角色管理</span>
                        </a>
                        <a href="/novel_creation/chapter_analysis" class="menu-item">
                            <span class="menu-item-label">章节分析</span>
                        </a>
                        <a href="/prompts" class="menu-item">
                            <span class="menu-item-label">提示词工程</span>
                        </a>
                        <a href="/knowledge" class="menu-item">
                            <span class="menu-item-label">知识库</span>
                        </a>
                    </div>

                    <div class="menu-section">
                        <div class="menu-section-title">创作工具集</div>
                        <a href="/novel_creation/opening_generator" class="menu-item">
                            <span class="menu-item-label">黄金开篇</span>
                        </a>
                        <a href="/novel_creation/title_generator" class="menu-item">
                            <span class="menu-item-label">书名生成</span>
                        </a>
                        <a href="/novel_creation/description_generator" class="menu-item">
                            <span class="menu-item-label">简介生成</span>
                        </a>
                        <a href="/novel_creation/name_generator" class="menu-item">
                            <span class="menu-item-label">名字生成</span>
                        </a>
                        <a href="/novel_creation/character_generator" class="menu-item">
                            <span class="menu-item-label">人设生成</span>
                        </a>
                        <a href="/novel_creation/cheat_generator" class="menu-item">
                            <span class="menu-item-label">金手指生成</span>
                        </a>
                        <a href="/novel_creation/cover_generator" class="menu-item">
                            <span class="menu-item-label">封面描述</span>
                        </a>
                        <a href="/novel_creation/short_story" class="menu-item">
                            <span class="menu-item-label">短篇创作</span>
                        </a>
                        <a href="/novel_creation/short_drama" class="menu-item">
                            <span class="menu-item-label">短剧剧本</span>
                        </a>
                    </div>

                    <div class="menu-section">
                        <div class="menu-section-title">高级功能</div>
                        <a href="/novel_creation/book_analysis" class="menu-item">
                            <span class="menu-item-label">拆书仿写</span>
                        </a>
                        <a href="/novel_creation/character_consistency" class="menu-item">
                            <span class="menu-item-label">一致性检查</span>
                        </a>
                        <a href="/ranking" class="menu-item">
                            <span class="menu-item-label">排行榜</span>
                        </a>
                    </div>

                    <div class="menu-section">
                        <div class="menu-section-title">账户与资产</div>
                        <a href="/user_center" class="menu-item">
                            <span class="menu-item-label">用户中心</span>
                        </a>
                        <a href="/membership" class="menu-item">
                            <span class="menu-item-label">会员 & 套餐</span>
                        </a>
                        <a href="/storage" class="menu-item">
                            <span class="menu-item-label">云存储空间</span>
                        </a>
                    </div>

                    <div class="menu-section">
                        <div class="menu-section-title">互动与信息</div>
                        <a href="/announcement" class="menu-item">
                            <span class="menu-item-label">站内公告</span>
                        </a>
                        <a href="/notice_bar" class="menu-item">
                            <span class="menu-item-label">通知栏</span>
                        </a>
                        <a href="/feedback" class="menu-item">
                            <span class="menu-item-label">意见反馈</span>
                        </a>
                        <a href="/crowdfunding" class="menu-item">
                            <span class="menu-item-label">创作众筹</span>
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <main class="frontend-dashboard-wrapper">
            <header class="frontend-header">
                <div>
                    <div class="frontend-header-title"><?= htmlspecialchars($siteName) ?> · 创作工作台</div>
                    <div class="frontend-header-subtitle">以「后台管理」视角总览你的创作与资产，快速进入常用功能</div>
                </div>
                <div class="frontend-header-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="/user_center" class="shortcut-link">进入用户中心</a>
                        <a href="/novel" class="shortcut-link">继续创作</a>
                        <button class="btn-primary" onclick="location.href='/novel'">开始创作</button>
                    <?php else: ?>
                        <a href="/login" class="shortcut-link">登录</a>
                        <a href="/register" class="shortcut-link">注册</a>
                        <button class="btn-primary" onclick="location.href='/novel'">免费开始</button>
                    <?php endif; ?>
                </div>
            </header>

            <!-- 复用后台的 v2 卡片风格，做前台总览卡 -->
            <section>
                <h2 class="frontend-section-title">创作与账户概览</h2>
                <div class="dashboard-v2">
                    <div class="dashboard-grid-v2">
                        <a href="/novel" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-novel">
                                <?= function_exists('render_icon') ? render_icon('book', ['width' => '32', 'height' => '32']) : '✒' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">小说创作</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">创作项目</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/music/project/list" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-music">
                                <?= function_exists('render_icon') ? render_icon('music', ['width' => '32', 'height' => '32']) : '♪' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">AI 音乐</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">作品数量</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/anime/project/list" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-anime">
                                <?= function_exists('render_icon') ? render_icon('film', ['width' => '32', 'height' => '32']) : '🎬' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">动漫制作</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">制作项目</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/knowledge" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-knowledge">
                                <?= function_exists('render_icon') ? render_icon('database', ['width' => '32', 'height' => '32']) : '📚' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">知识库</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">知识条目</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/prompts" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-prompts">
                                <?= function_exists('render_icon') ? render_icon('code', ['width' => '32', 'height' => '32']) : '💡' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">提示词工程</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">提示词模板</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/ranking" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-ranking">
                                <?= function_exists('render_icon') ? render_icon('trending-up', ['width' => '32', 'height' => '32']) : '📊' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">排行榜</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">热门作品</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/membership" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-user">
                                <?= function_exists('render_icon') ? render_icon('users', ['width' => '32', 'height' => '32']) : '👤' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">会员与权益</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">当前等级</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <a href="/storage" class="dashboard-card-v2">
                            <div class="card-icon-v2 bg-storage">
                                <?= function_exists('render_icon') ? render_icon('storage', ['width' => '32', 'height' => '32']) : '☁' ?>
                            </div>
                            <div class="card-content-v2">
                                <h3 class="card-title-v2">存储空间</h3>
                                <div class="card-stats-row">
                                    <div class="stat-item">
                                        <span class="stat-label">已用空间</span>
                                        <span class="stat-value">—</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <section class="frontend-grid">
                <div class="frontend-panel">
                    <h3 class="frontend-section-title">最近动态 / 公告</h3>
                    <ul class="frontend-list">
                        <li>欢迎来到 <?= htmlspecialchars($siteName) ?>，从左侧菜单或上方卡片开始你的创作旅程。</li>
                        <li>前往「创作众筹」探索更多用户项目与合作机会。</li>
                        <li>在「用户中心」中查看账户详情与使用记录。</li>
                    </ul>
                </div>

                <div class="frontend-panel">
                    <h3 class="frontend-section-title">快捷入口</h3>
                    <div class="shortcut-links">
                        <a href="/novel" class="shortcut-link">小说创作</a>
                        <a href="/novel_creation/editor" class="shortcut-link">智能编辑器</a>
                        <a href="/novel_creation/outline_generator" class="shortcut-link">大纲生成</a>
                        <a href="/novel_creation/character_manager" class="shortcut-link">角色管理</a>
                        <a href="/music/project/list" class="shortcut-link">AI 音乐</a>
                        <a href="/anime/project/list" class="shortcut-link">动画制作</a>
                        <a href="/knowledge" class="shortcut-link">知识库</a>
                        <a href="/prompts" class="shortcut-link">提示词工程</a>
                        <a href="/ranking" class="shortcut-link">排行榜</a>
                        <a href="/feedback" class="shortcut-link">提交反馈</a>
                        <a href="/crowdfunding" class="shortcut-link">众筹广场</a>
                    </div>
                </div>
            </section>

            <div class="frontend-footer">
                © <?= date('Y') ?> <?= htmlspecialchars($siteName) ?> · 前台首页 · 管理后台风格
            </div>
        </main>
    </div>
</body>
</html>
