<!DOCTYPE html>
<html lang="zh-CN" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '仪表盘') ?> - 星夜阁</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    // 主题版本号用于样式与脚本缓存控制
    $themeVersion = FrontendConfig::CACHE_VERSION;
    
    $currentPage = $currentPage ?? 'dashboard';
    $user = $user ?? null;
    $isFestive = FrontendConfig::isFestiveSeason();

    $topBarNotice = null;
    $topBarMarqueeText = '';
    $topBarNoticeItems = [];
    $allNoticesForModal = []; // 所有通知的完整数据，用于弹窗显示
    try {
        $notices = NoticeBar::getAll(null, 'enabled');
        if (!empty($notices)) {
            $texts = [];
            $maxPriority = null;

            foreach ($notices as $row) {
                $p = (int)($row['priority'] ?? 0);
                if ($p < 0) {
                    $p = 0;
                }

                $plainText = trim(strip_tags((string)($row['content'] ?? '')));
                if ($plainText === '') {
                    continue;
                }

                // 记录最高优先级的通知，用于初始显示
                if ($topBarNotice === null || $maxPriority === null || $p > $maxPriority) {
                    $topBarNotice = $row;
                    $maxPriority = $p;
                }

                // 收集所有通知的完整数据用于弹窗
                $allNoticesForModal[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'content' => (string)($row['content'] ?? ''),
                    'priority' => $p,
                    'link' => !empty($row['link']) ? (string)$row['link'] : null,
                    'created_at' => !empty($row['created_at']) ? (string)$row['created_at'] : null,
                ];

                if ($p <= 0) {
                    continue;
                }

                // 按优先级分档到 high/medium/low，供前端决定颜色
                if ($p >= 80) {
                    $level = 'high';
                    $weight = 3;
                } elseif ($p >= 40) {
                    $level = 'medium';
                    $weight = 2;
                } else {
                    $level = 'low';
                    $weight = 1;
                }

                // 用权重控制在跑马灯中的出现频次
                for ($i = 0; $i < $weight; $i++) {
                    $texts[] = [
                        'text'  => $plainText,
                        'level' => $level,
                    ];
                }
            }

            if (!empty($texts)) {
                shuffle($texts);
                $topBarNoticeItems = $texts;
                $first = $topBarNoticeItems[0] ?? null;
                if (is_array($first) && isset($first['text'])) {
                    $topBarMarqueeText = (string)$first['text'];
                } else {
                    $topBarMarqueeText = (string)$first;
                }
            }
        }
    } catch (\Throwable $e) {
        error_log('UserCenterLayout NoticeBar::getAll error: ' . $e->getMessage());
    }
    ?>
    <!-- 用户中心统一使用当前主题包的样式，而不再依赖 /static/frontend/web/css -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('style.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-tables.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/responsive-forms.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-base.css', $activeThemeId, $themeVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('shared/dashboard-v2-cards.css', $activeThemeId, $themeVersion)) ?>">
    <!-- 用户中心页面专用样式 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('pages/user-center.css', $activeThemeId, $themeVersion)) ?>">
    <!-- 创作中心页面样式（小说/动漫/音乐） -->
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('pages/creation-center.css', $activeThemeId, $themeVersion)) ?>">
    <?php if ($currentPage === 'novel_creation'): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('pages/novel-creation.css', $activeThemeId, $themeVersion)) ?>">
    <script src="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/modules/novel-creation.js', $themeVersion)) ?>"></script>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- 通知弹窗 JS -->
    <script src="<?= htmlspecialchars(FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_JS . '/modules/notice-modal.js', $themeVersion)) ?>"></script>
</head>
<body class="page-user-center">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="/user_center">
            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="sidebar-logo-img">
            <span class="sidebar-brand-name"><?= htmlspecialchars($siteName) ?></span>
            <?php if ($isFestive): ?>
                <span class="badge badge-festive">🏮 新春</span>
            <?php endif; ?>
        </a>
        
        <div class="sidebar-user uc-sidebar-user" id="sidebarUserDropdown" style="display: none;">
            <div class="sidebar-user-left" id="sidebarUserTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
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
                <!-- 创作入口（所有页面都显示） -->
                <div class="menu-section">
                    <div class="menu-section-title">创作</div>
                    <?php if ($currentPage === 'novel' || $currentPage === 'novel_creation'): ?>
                    <a href="/novel" class="menu-item <?= ($currentPage === 'novel') ? 'active' : '' ?>">
                        <?= icon('book', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的小说<?= $isFestive ? ' 🏮' : '' ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($currentPage === 'ai_music'): ?>
                    <a href="/music/project/list" class="menu-item active">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的音乐项目</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($currentPage === 'anime_production'): ?>
                    <a href="/anime/project/list" class="menu-item active">
                        <?= icon('video', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的动漫项目</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($currentPage === 'general_features'): ?>
                    <a href="/general_features" class="menu-item active">
                        <?= icon('settings', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">通用功能</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($currentPage === 'community'): ?>
                    <a href="/community" class="menu-item active">
                        <?= icon('users', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">社区</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!in_array($currentPage, ['novel', 'novel_creation', 'ai_music', 'anime_production', 'general_features', 'community'])): ?>
                    <a href="/novel" class="menu-item <?= ($currentPage === 'novel' || $currentPage === 'novel_creation') ? 'active' : '' ?>">
                        <?= icon('book', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的小说<?= $isFestive ? ' 🏮' : '' ?></span>
                    </a>
                    <a href="/music/project/list" class="menu-item <?= ($currentPage === 'ai_music') ? 'active' : '' ?>">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的音乐项目</span>
                    </a>
                    <a href="/anime/project/list" class="menu-item <?= ($currentPage === 'anime_production') ? 'active' : '' ?>">
                        <?= icon('video', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">我的动漫项目</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if ($currentPage === 'novel_creation'): ?>
                <!-- 小说创作中心专用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">策划 & 设定</div>
                    <a href="/novel_creation/outline_generator" class="menu-item">
                        <?= icon('list', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">生成大纲</span>
                    </a>
                    <a href="/novel_creation/character_manager" class="menu-item">
                        <?= icon('users', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">角色管理</span>
                    </a>
                    <a href="/knowledge" class="menu-item">
                        <?= icon('database', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">世界观 / 设定库</span>
                    </a>
                    <a href="/novel_creation/character_consistency" class="menu-item">
                        <?= icon('check-circle', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">一致性检查</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">写作助手</div>
                    <a href="/novel_creation/editor" class="menu-item">
                        <?= icon('edit-3', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">智能续写</span>
                    </a>
                    <a href="/novel_creation/editor" class="menu-item">
                        <?= icon('refresh-cw', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">改写 / 扩写 / 润色</span>
                    </a>
                    <a href="/novel_creation/chapter_analysis" class="menu-item">
                        <?= icon('bar-chart-2', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">章节分析</span>
                    </a>
                    <a href="/novel_creation/book_analysis" class="menu-item">
                        <?= icon('book-open', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">拆书仿写</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">灵感小工具</div>
                    <a href="/novel_creation/opening_generator" class="menu-item">
                        <?= icon('star', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">黄金开篇生成</span>
                    </a>
                    <a href="/novel_creation/title_generator" class="menu-item">
                        <?= icon('type', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">书名生成</span>
                    </a>
                    <a href="/novel_creation/description_generator" class="menu-item">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">简介生成</span>
                    </a>
                    <a href="/novel_creation/cheat_generator" class="menu-item">
                        <?= icon('zap', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">金手指设定</span>
                    </a>
                    <a href="/novel_creation/name_generator" class="menu-item">
                        <?= icon('tag', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">名字生成</span>
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
                <?php endif; ?>

                <?php if ($currentPage === 'ai_music'): ?>
                <!-- 音乐创作中心专用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">灵感 & 歌词</div>
                    <a href="/music/project/lyrics_generator" class="menu-item">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">歌词生成</span>
                    </a>
                    <a href="/music/project/lyrics_upload" class="menu-item">
                        <?= icon('upload', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">歌词上传 & 情感分析</span>
                    </a>
                    <a href="/music/project/inspiration" class="menu-item">
                        <?= icon('lightbulb', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">主题 / 情绪灵感板</span>
                    </a>
                    <a href="/music/project/sheet_upload" class="menu-item">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">曲谱上传识别</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">旋律 & 编曲</div>
                    <a href="/music/project/melody_generator" class="menu-item">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">旋律生成</span>
                    </a>
                    <a href="/music/project/humming_recognition" class="menu-item">
                        <?= icon('mic', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">哼唱识别成旋律</span>
                    </a>
                    <a href="/music/project/auto_arrangement" class="menu-item">
                        <?= icon('layers', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">自动编曲</span>
                    </a>
                    <a href="/music/project/chord_suggestion" class="menu-item">
                        <?= icon('sliders', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">和弦进行优化</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">音轨 & 人声</div>
                    <a href="/music/project/multi_track" class="menu-item">
                        <?= icon('sliders', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">多轨编辑器</span>
                    </a>
                    <a href="/music/project/vocal_synthesis" class="menu-item">
                        <?= icon('mic', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">AI 歌声合成</span>
                    </a>
                    <a href="/music/project/vocal_tuning" class="menu-item">
                        <?= icon('settings', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">人声修音 / 降噪</span>
                    </a>
                    <a href="/music/project/stem_separation" class="menu-item">
                        <?= icon('git-branch', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">AI 音轨分离 / 融合</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">混音 & 母带 & 导出</div>
                    <a href="/music/project/auto_mix" class="menu-item">
                        <?= icon('sliders', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">自动混音</span>
                    </a>
                    <a href="/music/project/mastering" class="menu-item">
                        <?= icon('star', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">自动母带</span>
                    </a>
                    <a href="/music/project/export" class="menu-item">
                        <?= icon('download', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">导出设置</span>
                    </a>
                    <a href="/music/project/mv_generator" class="menu-item">
                        <?= icon('video', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">生成音乐视频</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($currentPage === 'anime_production'): ?>
                <!-- 动漫创作中心专用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">企划 & 结构</div>
                    <a href="/anime/project/create" class="menu-item">
                        <?= icon('file-text', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">企划方案生成</span>
                    </a>
                    <a href="/anime/project/script_generator" class="menu-item">
                        <?= icon('edit', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">分集脚本生成</span>
                    </a>
                    <a href="/anime/project/storyline" class="menu-item">
                        <?= icon('git-branch', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">主线 / 支线管理</span>
                    </a>
                    <a href="/anime/project/foreshadowing" class="menu-item">
                        <?= icon('link', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">伏笔管理</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">视觉设定</div>
                    <a href="/anime/project/character_design" class="menu-item">
                        <?= icon('user', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">角色设计</span>
                    </a>
                    <a href="/anime/project/scene_design" class="menu-item">
                        <?= icon('image', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">场景设计</span>
                    </a>
                    <a href="/anime/project/storyboard" class="menu-item">
                        <?= icon('film', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">分镜生成</span>
                    </a>
                    <a href="/anime/project/action_suggestion" class="menu-item">
                        <?= icon('zap', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">动作 / 表情建议</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">动画 & 音视频</div>
                    <a href="/anime/project/keyframe" class="menu-item">
                        <?= icon('image', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">动画关键帧方案</span>
                    </a>
                    <a href="/anime/project/audio" class="menu-item">
                        <?= icon('music', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">配音 / 音效 / BGM方案</span>
                    </a>
                    <a href="/anime/project/video_synthesis" class="menu-item">
                        <?= icon('video', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">视频合成方案</span>
                    </a>
                    <a href="/anime/project/review" class="menu-item">
                        <?= icon('check-circle', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">审核与发布配置</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">短剧快速生成</div>
                    <a href="/anime/project/quick_generate" class="menu-item">
                        <?= icon('zap', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">一键生成短剧</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!in_array($currentPage, ['novel_creation', 'ai_music', 'anime_production'])): ?>
                <!-- 其他页面的通用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">小说助手</div>
                    <a href="/novel_creation/editor" class="menu-item">
                        <?= icon('edit-3', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">智能编辑器</span>
                    </a>
                    <a href="/novel_creation/outline_generator" class="menu-item">
                        <?= icon('list', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">大纲生成</span>
                    </a>
                    <a href="/novel_creation/character_manager" class="menu-item">
                        <?= icon('users', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">角色管理</span>
                    </a>
                    <a href="/novel_creation/chapter_analysis" class="menu-item">
                        <?= icon('bar-chart-2', ['width' => '20', 'height' => '20']) ?>
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
                <?php endif; ?>

                <?php if ($currentPage === 'general_features'): ?>
                <!-- 通用功能专用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">账户与配置</div>
                    <a href="/membership" class="menu-item">
                        <?= icon('award', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">会员 & 套餐</span>
                    </a>
                    <a href="/storage" class="menu-item">
                        <?= icon('hard-drive', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">云存储空间</span>
                    </a>
                    <a href="/user_center/profile" class="menu-item">
                        <?= icon('user', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">个人中心</span>
                    </a>
                    <a href="/user_center/starry_night_config" class="menu-item">
                        <?= icon('sliders', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">引擎配置</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($currentPage === 'community'): ?>
                <!-- 社区专用菜单 -->
                <div class="menu-section">
                    <div class="menu-section-title">社区功能</div>
                    <a href="/announcement" class="menu-item">
                        <?= icon('megaphone', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">站内公告</span>
                    </a>
                    <a href="/crowdfunding" class="menu-item">
                        <?= icon('heart', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">创作众筹</span>
                    </a>
                    <a href="/feedback" class="menu-item">
                        <?= icon('message-square', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">意见反馈</span>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!in_array($currentPage, ['general_features', 'community'])): ?>
                <!-- 通用功能菜单（所有页面都显示，除了通用功能和社区页面） -->
                <div class="menu-section">
                    <div class="menu-section-title">账户与配置</div>
                    <a href="/membership" class="menu-item <?= ($currentPage === 'membership') ? 'active' : '' ?>">
                        <?= icon('award', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">会员 & 套餐</span>
                    </a>
                    <a href="/storage" class="menu-item <?= ($currentPage === 'storage') ? 'active' : '' ?>">
                        <?= icon('hard-drive', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">云存储空间</span>
                    </a>
                    <a href="/user_center/profile" class="menu-item <?= ($currentPage === 'profile') ? 'active' : '' ?>">
                        <?= icon('user', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">个人中心</span>
                    </a>
                    <a href="/user_center/starry_night_config" class="menu-item <?= ($currentPage === 'starry_night_config') ? 'active' : '' ?>">
                        <?= icon('sliders', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">引擎配置</span>
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">社区与公告</div>
                    <a href="/announcement" class="menu-item <?= ($currentPage === 'announcement') ? 'active' : '' ?>">
                        <?= icon('megaphone', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">站内公告</span>
                    </a>
                    <a href="/crowdfunding" class="menu-item <?= ($currentPage === 'crowdfunding') ? 'active' : '' ?>">
                        <?= icon('heart', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">创作众筹</span>
                    </a>
                    <a href="/feedback" class="menu-item <?= ($currentPage === 'feedback') ? 'active' : '' ?>">
                        <?= icon('message-square', ['width' => '20', 'height' => '20']) ?>
                        <span class="nav-text">意见反馈</span>
                    </a>
                </div>
                <?php endif; ?>
                </nav>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="top-bar-mode-toggle" id="topBarModeToggle" type="button">
                    <span class="mode-toggle-icon">
                        <?= icon('grid', ['width' => '18', 'height' => '18']) ?>
                    </span>
                    <span class="mode-toggle-text">创作模式</span>
                </button>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="mode-switch-menu" id="modeSwitchMenu" aria-hidden="true">
                    <a href="/novel" class="mode-switch-item">
                        <span class="mode-switch-item-label">小说创作</span>
                    </a>
                    <a href="/music/project/list" class="mode-switch-item">
                        <span class="mode-switch-item-label">音乐创作</span>
                    </a>
                    <a href="/anime/project/list" class="mode-switch-item">
                        <span class="mode-switch-item-label">动漫制作</span>
                    </a>
                    <div class="mode-switch-divider"></div>
                    <a href="/general_features" class="mode-switch-item">
                        <span class="mode-switch-item-label">通用功能</span>
                    </a>
                    <a href="/community" class="mode-switch-item">
                        <span class="mode-switch-item-label">社区</span>
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
                        $noticeText = (string)($topBarMarqueeText ?? '');
                    ?>
                    <div class="top-bar-notice-pill notice-level-<?= htmlspecialchars($noticeLevel) ?>" id="topBarNoticePill" data-all-notices='<?= htmlspecialchars(json_encode($allNoticesForModal, JSON_UNESCAPED_UNICODE)) ?>' style="cursor: pointer;" title="点击查看所有通知">
                        <span class="notice-pill-label"><?= htmlspecialchars($noticeLabel) ?></span>
                        <span class="notice-pill-content">
                            <span class="notice-pill-content-inner" id="topBarNoticeMarqueeText" data-notice-items='<?= htmlspecialchars(json_encode($topBarNoticeItems, JSON_UNESCAPED_UNICODE)) ?>'>
                                <?= htmlspecialchars($noticeText) ?>
                            </span>
                        </span>
                    </div>
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
                    <div class="top-bar-user-trigger" id="topBarUserTrigger" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
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
            </div>
        </header>

        <div class="main-content-wrapper">
            <div class="content-container">
                <div class="content-body">
                    <?= $content ?? '' ?>
                </div>
            </div>
        </div>
    </main>

    <?php
    $jsVersion = FrontendConfig::CACHE_VERSION;
    ?>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('sidebar-toggle.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('components/sidebar.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('theme.js', $activeThemeId, $jsVersion)) ?>"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Dropdown] DOMContentLoaded fired');
    
    var sidebarTrigger = document.getElementById('sidebarUserTrigger');
    var sidebarPanel = document.getElementById('sidebarUserDropdownPanel');
    console.log('[Dropdown] sidebarTrigger:', sidebarTrigger, 'sidebarPanel:', sidebarPanel);
    
    if (sidebarTrigger && sidebarPanel) {
        console.log('[Dropdown] Attaching sidebar event listeners');
        function toggleSidebar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = sidebarPanel.classList.toggle('visible');
            console.log('[Dropdown] Sidebar toggle, open:', open, 'classes:', sidebarPanel.className);
            sidebarTrigger.setAttribute('aria-expanded', open);
            sidebarPanel.setAttribute('aria-hidden', !open);
        }
        function closeSidebar() {
            sidebarPanel.classList.remove('visible');
            sidebarTrigger.setAttribute('aria-expanded', 'false');
            sidebarPanel.setAttribute('aria-hidden', 'true');
        }
        sidebarTrigger.addEventListener('click', function(e) {
            console.log('[Dropdown] Sidebar trigger clicked');
            toggleSidebar(e);
        });
        sidebarTrigger.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleSidebar(); } });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#sidebarUserDropdown')) closeSidebar();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });
    }
    
    var topBarTrigger = document.getElementById('topBarUserTrigger');
    var topBarPanel = document.getElementById('topBarUserDropdownPanel');
    console.log('[Dropdown] topBarTrigger:', topBarTrigger, 'topBarPanel:', topBarPanel);
    
    if (topBarTrigger && topBarPanel) {
        console.log('[Dropdown] Attaching topbar event listeners');
        function toggleTopBar(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = topBarPanel.classList.toggle('visible');
            console.log('[Dropdown] Topbar toggle, open:', open, 'classes:', topBarPanel.className);
            console.log('[Dropdown] Topbar panel computed style:', window.getComputedStyle(topBarPanel).visibility, window.getComputedStyle(topBarPanel).opacity);
            topBarTrigger.setAttribute('aria-expanded', open);
            topBarPanel.setAttribute('aria-hidden', !open);
        }
        function closeTopBar() {
            topBarPanel.classList.remove('visible');
            topBarTrigger.setAttribute('aria-expanded', 'false');
            topBarPanel.setAttribute('aria-hidden', 'true');
        }
        topBarTrigger.addEventListener('click', function(e) {
            console.log('[Dropdown] Topbar trigger clicked');
            toggleTopBar(e);
        });
        topBarTrigger.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleTopBar(); } });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#topBarUserDropdown')) closeTopBar();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTopBar();
        });
    }

    var modeToggle = document.getElementById('topBarModeToggle');
    var modeMenu = document.getElementById('modeSwitchMenu');
    console.log('[Dropdown] modeToggle:', modeToggle, 'modeMenu:', modeMenu);
    
    if (modeToggle && modeMenu) {
        console.log('[Dropdown] Attaching mode toggle event listeners');
        function toggleModeMenu(e) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            var open = modeMenu.classList.toggle('visible');
            console.log('[Dropdown] Mode toggle, open:', open, 'classes:', modeMenu.className);
            console.log('[Dropdown] Mode menu computed style:', window.getComputedStyle(modeMenu).visibility, window.getComputedStyle(modeMenu).opacity);
            modeMenu.setAttribute('aria-hidden', !open);
        }
        function closeModeMenu() {
            modeMenu.classList.remove('visible');
            modeMenu.setAttribute('aria-hidden', 'true');
        }
        modeToggle.addEventListener('click', function(e) {
            console.log('[Dropdown] Mode toggle clicked');
            toggleModeMenu(e);
        });
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
});
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('topBarNoticeMarqueeText');
    if (!el) return;

    var pill = document.getElementById('topBarNoticePill');
    var container = el.parentElement; // .notice-pill-content

    var raw = el.getAttribute('data-notice-items') || '[]';
    var list;
    try {
        list = JSON.parse(raw);
    } catch (e) {
        list = [];
    }
    if (!Array.isArray(list) || list.length === 0) return;

    function normalizeItem(idx) {
        var item = list[idx];
        if (typeof item === 'string') {
            return { text: item, level: null };
        }
        if (item && typeof item === 'object') {
            return {
                text: String(item.text || ''),
                level: item.level || null
            };
        }
        return { text: '', level: null };
    }

    function applyLevel(level) {
        if (!pill || !level) return;
        pill.classList.remove('notice-level-high', 'notice-level-medium', 'notice-level-low');
        pill.classList.add('notice-level-' + level);
    }

    // ===== 跑马灯速度控制 =====
    // 以固定像素/秒控制阅读速度：文字越长，动画越久 → 不会“单句话跑太快”
    var PX_PER_SEC = 28;          // 越小越慢，只影响滚动速度
    var MIN_MS = 18000;           // 最短一轮滚动时长
    var MAX_MS = 90000;           // 最长一轮滚动时长
    // 多条通知之间的切换等待时间（不读完整个滚动就可以切下一条）
    var DEFAULT_SWITCH_MS = 8000; // 无需滚动（文字不超宽）时，单条通知大约停留 8 秒

    function computeDurationMs() {
        if (!container) return 30000;
        // scrollWidth 是纯文本宽度；动画实际需要走过的距离≈文本宽度 + 容器宽度（对应 padding-left:100%）
        var textW = el.scrollWidth || 0;
        var boxW = container.getBoundingClientRect().width || 0;
        if (textW <= 0 || boxW <= 0) return 30000;

        // 文字没超出容器：不需要滚动
        if (textW <= boxW + 2) return 0;

        // keyframes 使用 translateX(calc(-100% - 100%))，等价于走 2 倍元素宽度
        // 元素宽度≈文本宽度 + 容器宽度（padding-left:100%），因此这里乘 2 匹配真实滚动距离
        var distance = (textW + boxW) * 2;
        var ms = Math.round((distance / PX_PER_SEC) * 1000);
        if (ms < MIN_MS) ms = MIN_MS;
        if (ms > MAX_MS) ms = MAX_MS;
        return ms;
    }

    // 根据滚动时长，换算“切到下一条”的等待时间
    // 规则：大约用滚动时长的 60%，并限制在 [8s, 20s] 区间内，避免“等太久”
    function getSwitchIntervalMs(scrollMs) {
        if (!scrollMs || scrollMs <= 0) return DEFAULT_SWITCH_MS;
        var MIN_SWITCH_MS = 8000;
        var MAX_SWITCH_MS = 20000;
        var interval = Math.round(scrollMs * 0.6);
        if (interval < MIN_SWITCH_MS) interval = MIN_SWITCH_MS;
        if (interval > MAX_SWITCH_MS) interval = MAX_SWITCH_MS;
        return interval;
    }

    function applyMarqueeForCurrentText() {
        var ms = computeDurationMs();

        if (ms === 0) {
            // 静态显示：必须把 padding-left 从 100% 改成 0，否则文字会被顶到右侧看不到
            el.classList.remove('notice-marquee-running');
            el.style.setProperty('--notice-marquee-duration', '');
            el.style.paddingLeft = '0';
            el.style.transform = 'none';
            el.style.animation = 'none';
            return 0;
        }

        // 恢复跑马灯样式（清掉静态显示的 inline 覆盖）
        el.style.animation = '';
        el.style.paddingLeft = '';
        el.style.transform = '';
        el.style.setProperty('--notice-marquee-duration', ms + 'ms');

        // 重置动画让新 duration 立刻生效
        el.classList.remove('notice-marquee-running');
        void el.offsetWidth;
        el.classList.add('notice-marquee-running');
        return ms;
    }

    // 如果只有一个通知，不需要轮播
    if (list.length === 1) {
        // 单条也要根据内容长度动态放慢速度；并且如果不需要滚动就静态显示
        // 用 rAF 等布局完成，确保 scrollWidth / container width 可靠
        requestAnimationFrame(function () {
            applyMarqueeForCurrentText();
        });
        return;
    }

    var idx = 0;
    var timer = null;
    var currentIntervalMs = 0;

    function restartTimer(intervalMs) {
        if (timer) clearInterval(timer);
        currentIntervalMs = intervalMs;
        timer = setInterval(function () {
            nextNotice();
        }, currentIntervalMs);
    }

    function nextNotice() {
        idx = (idx + 1) % list.length;
        var item = normalizeItem(idx);

        el.textContent = item.text;
        if (item.level) {
            applyLevel(item.level);
        }

        // 应用滚动速度（只调滚动，不调切换逻辑）
        var scrollMs = applyMarqueeForCurrentText();
        // 由滚动时长算出“切下一条”的等待时间（不必等整个滚动结束）
        var intervalMs = getSwitchIntervalMs(scrollMs);
        if (intervalMs !== currentIntervalMs) restartTimer(intervalMs);
    }

    // 初始显示第一个通知
    var first = normalizeItem(0);
    el.textContent = first.text;
    if (first.level) {
        applyLevel(first.level);
    }
    // 初始应用跑马灯速度并启动轮播定时器
    var firstScrollMs = applyMarqueeForCurrentText();
    var firstIntervalMs = getSwitchIntervalMs(firstScrollMs);
    restartTimer(firstIntervalMs);

    // 监听动画结束事件作为备用机制
    el.addEventListener('animationend', function () {
        // 如果定时器还在运行，这里不需要再次调用 nextNotice
        // 但可以确保动画正确结束
    });

    // 窗口尺寸变化时重新计算（只影响滚动速度）
    window.addEventListener('resize', function () {
        var scrollMs = applyMarqueeForCurrentText();
        var intervalMs = getSwitchIntervalMs(scrollMs);
        if (intervalMs !== currentIntervalMs) restartTimer(intervalMs);
    });

    // 页面卸载时清理定时器
    window.addEventListener('beforeunload', function() {
        if (timer) {
            clearInterval(timer);
        }
    });
});
    </script>
    
    <!-- 通知弹窗 -->
    <div id="noticeModal" class="notice-modal" style="display: none;">
        <div class="notice-modal-overlay"></div>
        <div class="notice-modal-content">
            <div class="notice-modal-header">
                <h2 class="notice-modal-title">所有通知</h2>
                <button class="notice-modal-close" id="noticeModalClose" aria-label="关闭">&times;</button>
            </div>
            <div class="notice-modal-body" id="noticeModalBody">
                <!-- 通知列表将在这里动态渲染 -->
            </div>
        </div>
    </div>
</body>
</html>
