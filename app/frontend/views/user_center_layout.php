<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - <?= htmlspecialchars($title ?? '仪表盘') ?></title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php 
    use app\models\Setting;
    use app\models\NoticeBar;
    use app\services\ThemeManager;
    use app\config\FrontendConfig;
    
    try {
        $siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
        $siteLogo = Setting::get('site_logo') ?: '/static/logo/logo.png';
    } catch (\Throwable $e) {
        error_log('Layout Setting::get() error: ' . $e->getMessage());
        $siteName = (string)get_env('APP_NAME', '星夜阁');
        $siteLogo = '/static/logo/logo.png';
    }
    
    $themeManager = new ThemeManager();
    $activeThemeId = $themeManager->getActiveThemeId(FrontendConfig::THEME_TYPE_WEB) ?? FrontendConfig::THEME_DEFAULT;
    $themeBasePath = FrontendConfig::getThemePath($activeThemeId);
    
    $currentPage = $currentPage ?? 'dashboard';
    $user = $user ?? null;
    $isFestive = FrontendConfig::isFestiveSeason();

    // 顶部导航通知栏：
    // - 后端可以启用多条；
    // - priority 作为 0~10 的「优先权重」，权重越高重复次数越多；
    // - 根据权重生成一个文本列表，随机打散后用作跑马灯内容；
    // - 同时保留一条最高权重记录用于决定颜色等级 / 标签文案。
    $topBarNotice = null;
    $topBarMarqueeText = '';
    $topBarNoticeItems = [];
    try {
        $notices = NoticeBar::getAll(null, 'enabled');
        if (!empty($notices)) {
            $texts = [];
            $maxPriority = null;

            foreach ($notices as $row) {
                // 0~10 的权重，越高重复次数越多
                $p = (int)($row['priority'] ?? 0);
                if ($p < 0) $p = 0;
                if ($p > 10) $p = 10;

                $plainText = trim(strip_tags((string)($row['content'] ?? '')));
                if ($plainText === '') {
                    continue;
                }

                // 记录最高权重，用于颜色/标签
                if ($topBarNotice === null || $maxPriority === null || $p > $maxPriority) {
                    $topBarNotice = $row;
                    $maxPriority = $p;
                }

                // 权重为 0 的不参与
                if ($p <= 0) {
                    continue;
                }

                // 根据权重重复加入多次，稍后整体打乱顺序
                for ($i = 0; $i < $p; $i++) {
                    $texts[] = $plainText;
                }
            }

            if (!empty($texts)) {
                // 打乱顺序，避免相同文案扎堆
                shuffle($texts);
                $topBarNoticeItems = $texts;
                // 初始先显示第一条
                $topBarMarqueeText = (string)$topBarNoticeItems[0];
            }
        }
    } catch (\Throwable $e) {
        error_log('UserCenterLayout NoticeBar::getAll error: ' . $e->getMessage());
    }
    ?>
    <?php
    
    ?>
    <!-- 共享基础样式（通过 FrontendConfig 生成，兼容 /public 与非 /public 部署） -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/style.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-tables.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-forms.css', $activeThemeId, $themeVersion)) ?>">
    <!-- 仪表盘卡片样式 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-base.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-v2-cards.css', $activeThemeId, $themeVersion)) ?>">
    <!-- 用户中心内容区样式（包含头像下拉与顶部导航样式） -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('pages/user-center.css', $activeThemeId, $themeVersion)) ?>">
    <!-- 只使用 CDN Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="/user_center">
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="sidebar-logo-img">
            <span class="sidebar-brand-name"><?= htmlspecialchars($siteName) ?></span>
            <?php if ($isFestive): ?>
                <span class="badge badge-festive" style="margin-left:auto;font-size:11px;">🏮 新春版</span>
            <?php endif; ?>
        </a>
        <div class="sidebar-user uc-sidebar-user" id="sidebarUserDropdown" style="display: none;">
            <div class="sidebar-user-left" id="sidebarUserTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" style="cursor:pointer;flex:1">
                <div class="sidebar-user-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像">
                    <?php else: ?>
                        <span class="avatar-placeholder"><?= mb_substr(htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用'), 0, 1) ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-meta">
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用户') ?></div>
                    <div class="sidebar-user-status"><?= $isFestive ? '🏮 灵感如泉' : '在线' ?></div>
                </div>
            </div>
            <div class="uc-avatar-dropdown" id="sidebarUserDropdownPanel" aria-hidden="true">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                        <?php else: ?>
                            <span><?= mb_substr(htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用'), 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown-user-info">
                        <div class="dropdown-name"><?= htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用户') ?></div>
                        <div class="dropdown-username">@<?= htmlspecialchars($user['username'] ?? $user['id'] ?? '') ?></div>
                    </div>
                </div>
                <div class="dropdown-section">
                    <div class="dropdown-row">
                        <span class="dropdown-label"><?= htmlspecialchars($dropdownMembership['level_name'] ?? '普通用户') ?></span>
                        <span class="dropdown-value"><?= ($user['status'] ?? '') === 'active' ? '正常' : htmlspecialchars($user['status'] ?? '正常') ?></span>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">包月会员：</span>
                        <span class="dropdown-value"><?= $dropdownMembership ? '已开通' : '未开通' ?></span>
                        <?php if (!$dropdownMembership): ?>
                            <a href="/membership" class="dropdown-link">开通</a>
                        <?php endif; ?>
                    </div>
                    <?php 
                    $dailyLimit = (int)($dropdownLimits['daily_word_limit'] ?? 10000);
                    $todayUsed = $dropdownTodayConsumed ?? 0;
                    $tokenBalance = (int)($dropdownTokenBalance['balance'] ?? $user['token_balance'] ?? 0);
                    ?>
                    <div class="dropdown-row">
                        <span class="dropdown-label">今日额度：</span>
                        <span class="dropdown-value"><?= number_format($todayUsed) ?> / <?= number_format($dailyLimit) ?></span>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">总额度：</span>
                        <span class="dropdown-value"><?= number_format($tokenBalance) ?></span>
                        <a href="/membership/token-records" class="dropdown-link">详情→</a>
                    </div>
                </div>
                <div class="dropdown-section">
                    <div class="dropdown-row">
                        <span class="dropdown-label">邮箱：</span>
                        <span class="dropdown-value"><?= htmlspecialchars($user['email'] ?? '未绑定') ?></span>
                        <a href="/user_center/profile#email" class="dropdown-link">换绑</a>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">手机：</span>
                        <span class="dropdown-value"><?= !empty($user['phone']) ? preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $user['phone']) : '未绑定' ?></span>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">微信：</span>
                        <span class="dropdown-value"><?= !empty($user['wechat_openid'] ?? null) ? '已绑定' : '绑定微信' ?></span>
                    </div>
                </div>
                <div class="dropdown-section">
                    <div class="dropdown-row">
                        <span class="dropdown-label">用户ID</span>
                        <span class="dropdown-value"><?= (int)($user['id'] ?? 0) ?></span>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">邀请码</span>
                        <span class="dropdown-value"><?= htmlspecialchars(strtoupper(substr(md5('uc_' . ($user['id'] ?? 0)), 0, 8))) ?></span>
                    </div>
                    <div class="dropdown-row">
                        <span class="dropdown-label">注册时间</span>
                        <span class="dropdown-value"><?= !empty($user['created_at']) ? date('Y/n/j', strtotime($user['created_at'])) : '-' ?></span>
                    </div>
                </div>
                <div class="dropdown-actions dropdown-actions-bottom">
                    <a href="/user_center/profile" class="dropdown-item">个人中心</a>
                    <a href="/logout" class="dropdown-item dropdown-item-danger">退出登录</a>
                </div>
            </div>
            <a class="sidebar-logout" href="/logout" title="退出登录">
                <?= icon('logout', ['width' => '18', 'height' => '18']) ?>
            </a>
        </div>

        <div class="sidebar-menu-wrapper">
            <div class="sidebar-menu-card">
                <nav>
                <div class="menu-section">
                    <div class="menu-section-title">创作</div>
                    <a href="/novel" class="menu-item <?= ($currentPage === 'novel') ? 'active' : '' ?>">
                        <?= icon('book', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的小说<?= $isFestive ? ' 🏮' : '' ?></span>
                    </a>
                    <a href="/novel_creation" class="menu-item <?= ($currentPage === 'novel_creation') ? 'active' : '' ?>">
                        <?= icon('book', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">小说工作台</span>
                    </a>
                    <a href="/ai_music" class="menu-item <?= ($currentPage === 'ai_music') ? 'active' : '' ?>">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">AI音乐创作</span>
                    </a>
                    <a href="/anime_production" class="menu-item <?= ($currentPage === 'anime_production') ? 'active' : '' ?>">
                        <?= icon('activity', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">动漫制作</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">小说助手</div>
                    <a href="/novel_creation/editor" class="menu-item">
                        <?= icon('edit', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">智能编辑器</span>
                    </a>
                    <a href="/novel_creation/outline_generator" class="menu-item">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">大纲生成</span>
                    </a>
                    <a href="/novel_creation/character_manager" class="menu-item">
                        <?= icon('users', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">角色管理</span>
                    </a>
                    <a href="/novel_creation/chapter_analysis" class="menu-item">
                        <?= icon('bar-chart', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">章节分析</span>
                    </a>
                    <a href="/prompts" class="menu-item <?= ($currentPage === 'prompts') ? 'active' : '' ?>">
                        <?= icon('code', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">提示词工程</span>
                    </a>
                    <a href="/knowledge" class="menu-item <?= ($currentPage === 'knowledge') ? 'active' : '' ?>">
                        <?= icon('database', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">知识库</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">工具箱</div>
                    <a href="/templates" class="menu-item <?= ($currentPage === 'templates') ? 'active' : '' ?>">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">模板库</span>
                    </a>
                    <a href="/agents" class="menu-item <?= ($currentPage === 'agents') ? 'active' : '' ?>">
                        <?= icon('cpu', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">智能体</span>
                    </a>
                    <a href="/share" class="menu-item <?= ($currentPage === 'share') ? 'active' : '' ?>">
                        <?= icon('share-2', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">资源分享</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">灵感与设定</div>
                    <a href="/novel_creation/opening_generator" class="menu-item">
                        <?= icon('star', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">黄金开篇</span>
                    </a>
                    <a href="/novel_creation/title_generator" class="menu-item">
                        <?= icon('type', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">书名生成</span>
                    </a>
                    <a href="/novel_creation/description_generator" class="menu-item">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">简介生成</span>
                    </a>
                    <a href="/novel_creation/name_generator" class="menu-item">
                        <?= icon('tag', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">名字生成</span>
                    </a>
                    <a href="/novel_creation/character_generator" class="menu-item">
                        <?= icon('user-plus', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">人设生成</span>
                    </a>
                    <a href="/novel_creation/cheat_generator" class="menu-item">
                        <?= icon('zap', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">金手指生成</span>
                    </a>
                    <a href="/novel_creation/cover_generator" class="menu-item">
                        <?= icon('image', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">封面描述</span>
                    </a>
                    <a href="/novel_creation/worldview_generator" class="menu-item">
                        <?= icon('globe', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">世界观生成</span>
                    </a>
                    <a href="/novel_creation/brainstorm_generator" class="menu-item">
                        <?= icon('lightbulb', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">脑洞生成</span>
                    </a>
                    <a href="/novel_creation/short_story" class="menu-item">
                        <?= icon('book-open', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">短篇创作</span>
                    </a>
                    <a href="/novel_creation/short_drama" class="menu-item">
                        <?= icon('film', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">短剧剧本</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">进阶创作</div>
                    <a href="/novel_creation/book_analysis" class="menu-item">
                        <?= icon('book-open', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">拆书仿写</span>
                    </a>
                    <a href="/novel_creation/character_consistency" class="menu-item">
                        <?= icon('check-circle', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">一致性检查</span>
                    </a>
                    <a href="/user_center/consistency_config" class="menu-item <?= ($currentPage === 'consistency_config') ? 'active' : '' ?>">
                        <?= icon('settings', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">一致性配置</span>
                    </a>
                    <a href="/ranking" class="menu-item <?= ($currentPage === 'ranking') ? 'active' : '' ?>">
                        <?= icon('trending-up', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">排行榜</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">账户与配置</div>
                    <a href="/membership" class="menu-item <?= ($currentPage === 'membership') ? 'active' : '' ?>">
                        <?= icon('users', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">会员 & 套餐</span>
                    </a>
                    <a href="/storage" class="menu-item <?= ($currentPage === 'storage') ? 'active' : '' ?>">
                        <?= icon('storage', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">云存储空间</span>
                    </a>
                    <a href="/user_center/profile" class="menu-item <?= ($currentPage === 'profile') ? 'active' : '' ?>">
                        <?= icon('user', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">个人中心</span>
                    </a>
                    <a href="/user_center/starry_night_config" class="menu-item <?= ($currentPage === 'starry_night_config') ? 'active' : '' ?>">
                        <?= icon('plugins', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">引擎配置</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">社区与公告</div>
                    <a href="/announcement" class="menu-item <?= ($currentPage === 'announcement') ? 'active' : '' ?>">
                        <?= icon('book', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">站内公告</span>
                    </a>
                    <a href="/crowdfunding" class="menu-item <?= ($currentPage === 'crowdfunding') ? 'active' : '' ?>">
                        <?= icon('activity', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">创作众筹</span>
                    </a>
                    <a href="/feedback" class="menu-item <?= ($currentPage === 'feedback') ? 'active' : '' ?>">
                        <?= icon('mail', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">意见反馈</span>
                    </a>
                </div>
                </nav>
            </div>
        </div>

    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left">
                <!-- 顶部功能切换按钮：在主要功能场景之间快速切换 -->
                <button class="top-bar-mode-toggle" id="topBarModeToggle" type="button">
                    <span class="mode-toggle-icon">
                        <?= icon('grid', ['width' => '18', 'height' => '18']) ?>
                    </span>
                    <span class="mode-toggle-text">创作场景</span>
                </button>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="mode-switch-menu" id="modeSwitchMenu" aria-hidden="true">
                    <a href="/novel_creation" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('book', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">小说创作</span>
                    </a>
                    <a href="/ai_music" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('music', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">AI 音乐</span>
                    </a>
                    <a href="/anime_production" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('activity', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">动画制作</span>
                    </a>
                    <a href="/knowledge" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('database', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">知识库</span>
                    </a>
                    <a href="/templates" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('file-text', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">模板库</span>
                    </a>
                    <a href="/agents" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('cpu', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">智能体</span>
                    </a>
                    <a href="/ranking" class="mode-switch-item">
                        <span class="mode-switch-item-icon">
                            <?= icon('trending-up', ['width' => '16', 'height' => '16']) ?>
                        </span>
                        <span class="mode-switch-item-label">排行榜</span>
                    </a>
                </div>
            </div>
            <div class="top-bar-center">
                <?php if (!empty($topBarNotice) && !empty($topBarNoticeItems)): ?>
                    <?php
                        $priority = (int)($topBarNotice['priority'] ?? 0);
                        if ($priority >= 80) {
                            $noticeLevel = 'high';
                            $noticeLabel = '重要通知';
                        } elseif ($priority >= 40) {
                            $noticeLevel = 'medium';
                            $noticeLabel = '提醒';
                        } else {
                            $noticeLevel = 'low';
                            $noticeLabel = '提示';
                        }
                        // 跑马灯展示经过权重拼接后的文本
                        $noticeText = (string)($topBarMarqueeText ?? '');
                        $noticeLink = $topBarNotice['link'] ?? '/notice_bar';
                    ?>
                    <a href="<?= htmlspecialchars($noticeLink) ?>"
                       class="top-bar-notice-pill notice-level-<?= htmlspecialchars($noticeLevel) ?>"
                       title="<?= htmlspecialchars($noticeText) ?>">
                        <span class="notice-pill-label"><?= htmlspecialchars($noticeLabel) ?></span>
                        <span class="notice-pill-content">
                            <span
                                class="notice-pill-content-inner"
                                id="topBarNoticeMarqueeText"
                                data-notice-items='<?= htmlspecialchars(json_encode($topBarNoticeItems, JSON_UNESCAPED_UNICODE)) ?>'
                            >
                                <?= htmlspecialchars($noticeText) ?>
                            </span>
                        </span>
                    </a>
                <?php else: ?>
                    <div class="top-bar-notice-pill top-bar-notice-pill-empty">
                        <span class="notice-pill-content">
                            <span class="notice-pill-content-inner" style="padding-left: 0; animation: none;">
                                暂无通知
                            </span>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-actions">
                <a href="/chat" class="icon-btn" title="对话">
                    <?= icon('message-circle', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">对话</span>
                </a>
                <a href="/messages" class="icon-btn" title="消息">
                    <?= icon('mail', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">消息</span>
                </a>
                <a href="/notifications" class="icon-btn" title="通知">
                    <?= icon('bell', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">通知</span>
                </a>
                <a href="/membership/recharge" class="icon-btn" title="充值">
                    <?= icon('credit-card', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">充值</span>
                </a>
                <a href="/history" class="icon-btn" title="历史">
                    <?= icon('clock', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">历史</span>
                </a>
                <a href="/tutorial" class="icon-btn" title="教程">
                    <?= icon('book-open', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">教程</span>
                </a>
                <a href="/user_center/profile" class="icon-btn" title="设置">
                    <?= icon('settings', ['width' => '18', 'height' => '18']) ?>
                    <span class="icon-btn-text">设置</span>
                </a>
                <div class="top-bar-user" id="topBarUserDropdown">
                <div class="top-bar-user-trigger" id="topBarUserTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" style="cursor:pointer">
                    <div class="top-bar-user-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="头像">
                        <?php else: ?>
                            <span class="avatar-placeholder"><?= mb_substr(htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用'), 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="top-bar-user-meta">
                        <div class="top-bar-user-name"><?= htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用户') ?></div>
                        <div class="top-bar-user-status"><?= $isFestive ? '🏮 灵感如泉' : '在线' ?></div>
                    </div>
                </div>
                <div class="uc-avatar-dropdown" id="topBarUserDropdownPanel" aria-hidden="true">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                            <?php else: ?>
                                <span><?= mb_substr(htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用'), 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-user-info">
                            <div class="dropdown-name"><?= htmlspecialchars($user['nickname'] ?? $user['username'] ?? '用户') ?></div>
                            <div class="dropdown-username">@<?= htmlspecialchars($user['username'] ?? $user['id'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="dropdown-section">
                        <div class="dropdown-row">
                            <span class="dropdown-label"><?= htmlspecialchars($dropdownMembership['level_name'] ?? '普通用户') ?></span>
                            <span class="dropdown-value"><?= ($user['status'] ?? '') === 'active' ? '正常' : htmlspecialchars($user['status'] ?? '正常') ?></span>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">包月会员：</span>
                            <span class="dropdown-value"><?= $dropdownMembership ? '已开通' : '未开通' ?></span>
                            <?php if (!$dropdownMembership): ?>
                                <a href="/membership" class="dropdown-link">开通</a>
                            <?php endif; ?>
                        </div>
                        <?php 
                        $dailyLimit = (int)($dropdownLimits['daily_word_limit'] ?? 10000);
                        $todayUsed = $dropdownTodayConsumed ?? 0;
                        $tokenBalance = (int)($dropdownTokenBalance['balance'] ?? $user['token_balance'] ?? 0);
                        ?>
                        <div class="dropdown-row">
                            <span class="dropdown-label">今日额度：</span>
                            <span class="dropdown-value"><?= number_format($todayUsed) ?> / <?= number_format($dailyLimit) ?></span>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">总额度：</span>
                            <span class="dropdown-value"><?= number_format($tokenBalance) ?></span>
                            <a href="/membership/token-records" class="dropdown-link">详情→</a>
                        </div>
                    </div>
                    <div class="dropdown-section">
                        <div class="dropdown-row">
                            <span class="dropdown-label">邮箱：</span>
                            <span class="dropdown-value"><?= htmlspecialchars($user['email'] ?? '未绑定') ?></span>
                            <a href="/user_center/profile#email" class="dropdown-link">换绑</a>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">手机：</span>
                            <span class="dropdown-value"><?= !empty($user['phone']) ? preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $user['phone']) : '未绑定' ?></span>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">微信：</span>
                            <span class="dropdown-value"><?= !empty($user['wechat_openid'] ?? null) ? '已绑定' : '绑定微信' ?></span>
                        </div>
                    </div>
                    <div class="dropdown-section">
                        <div class="dropdown-row">
                            <span class="dropdown-label">用户ID</span>
                            <span class="dropdown-value"><?= (int)($user['id'] ?? 0) ?></span>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">邀请码</span>
                            <span class="dropdown-value"><?= htmlspecialchars(strtoupper(substr(md5('uc_' . ($user['id'] ?? 0)), 0, 8))) ?></span>
                        </div>
                        <div class="dropdown-row">
                            <span class="dropdown-label">注册时间</span>
                            <span class="dropdown-value"><?= !empty($user['created_at']) ? date('Y/n/j', strtotime($user['created_at'])) : '-' ?></span>
                        </div>
                    </div>
                    <div class="dropdown-actions dropdown-actions-bottom">
                        <a href="/user_center/profile" class="dropdown-item">个人中心</a>
                        <a href="/logout" class="dropdown-item dropdown-item-danger">退出登录</a>
                    </div>
                </div>
                </div>
            </div> <!-- /.user-actions -->
        </div>

        <div class="main-content-wrapper">
            <div class="content-container">
                <div class="content-body">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    $jsVersion = FrontendConfig::CACHE_VERSION;
    ?>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('sidebar-toggle.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('components/sidebar.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('theme.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script>
(function() {
    // 侧边栏用户下拉框（已隐藏，保留代码以防需要）
    var sidebarTrigger = document.getElementById('sidebarUserTrigger');
    var sidebarPanel = document.getElementById('sidebarUserDropdownPanel');
    if (sidebarTrigger && sidebarPanel) {
        function toggleSidebar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = sidebarPanel.classList.toggle('visible');
            sidebarTrigger.setAttribute('aria-expanded', open);
            sidebarPanel.setAttribute('aria-hidden', !open);
        }
        function closeSidebar() {
            sidebarPanel.classList.remove('visible');
            sidebarTrigger.setAttribute('aria-expanded', 'false');
            sidebarPanel.setAttribute('aria-hidden', 'true');
        }
        sidebarTrigger.addEventListener('click', toggleSidebar);
        sidebarTrigger.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSidebar(); } });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#sidebarUserDropdown')) closeSidebar();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    }
    
    // 顶部导航栏用户下拉框
    var topBarTrigger = document.getElementById('topBarUserTrigger');
    var topBarPanel = document.getElementById('topBarUserDropdownPanel');
    if (topBarTrigger && topBarPanel) {
        function toggleTopBar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = topBarPanel.classList.toggle('visible');
            topBarTrigger.setAttribute('aria-expanded', open);
            topBarPanel.setAttribute('aria-hidden', !open);
        }
        function closeTopBar() {
            topBarPanel.classList.remove('visible');
            topBarTrigger.setAttribute('aria-expanded', 'false');
            topBarPanel.setAttribute('aria-hidden', 'true');
        }
        topBarTrigger.addEventListener('click', toggleTopBar);
        topBarTrigger.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleTopBar(); } });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#topBarUserDropdown')) closeTopBar();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTopBar();
        });
    }

    // 顶部模式切换按钮：在主要功能页面之间切换
    var modeToggle = document.getElementById('topBarModeToggle');
    var modeMenu = document.getElementById('modeSwitchMenu');
    if (modeToggle && modeMenu) {
        function toggleModeMenu(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = modeMenu.classList.toggle('visible');
            modeMenu.setAttribute('aria-hidden', !open);
        }
        function closeModeMenu() {
            modeMenu.classList.remove('visible');
            modeMenu.setAttribute('aria-hidden', 'true');
        }
        modeToggle.addEventListener('click', toggleModeMenu);
        modeToggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleModeMenu();
            }
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.top-bar-left')) {
                closeModeMenu();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModeMenu();
            }
        });
    }
})();
    </script>
</body>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('topBarNoticeMarqueeText');
    if (!el) return;

    var raw = el.getAttribute('data-notice-items') || '[]';
    var list;
    try {
        list = JSON.parse(raw);
    } catch (e) {
        list = [];
    }
    if (!Array.isArray(list) || list.length === 0) return;

    var idx = 0;

    function nextNotice() {
        idx = (idx + 1) % list.length;
        el.textContent = list[idx];
        // 重置动画：移除再强制重排后添加
        el.classList.remove('notice-marquee-running');
        // 触发回流
        void el.offsetWidth;
        el.classList.add('notice-marquee-running');
    }

    // 初始添加一个标记类以便重启动画
    el.classList.add('notice-marquee-running');

    el.addEventListener('animationend', function () {
        nextNotice();
    });
});
</script>
</html>
