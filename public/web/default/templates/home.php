<?php
use app\models\Setting;
use app\config\FrontendConfig;

$siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
$siteLogo = Setting::get('site_logo');
$isFestive = FrontendConfig::isFestiveSeason();

// 确保根据 remember_login Cookie 恢复“保持30天登录”的会话
if (function_exists('frontend_auto_login_from_cookie')) {
    frontend_auto_login_from_cookie();
}

// 当前登录状态
$isLoggedIn = !empty($_SESSION['user_logged_in']) && !empty($_SESSION['user_id']);

// 从后台获取首页设置
$homeSettings = Setting::getMany([
    // 英雄区域
    'home_hero_badge',           // 徽章文字
    'home_hero_title',           // 主标题
    'home_hero_subtitle',        // 副标题
    'home_hero_cta_primary',     // 主按钮文字
    'home_hero_cta_secondary',   // 次按钮文字
    
    // 统计数据
    'home_stat_users',           // 活跃创作者数量
    'home_stat_novels',          // 创作作品数量
    'home_stat_words',           // 生成字数
    
    // SEO设置
    'home_meta_description',
    'home_meta_keywords',
    
    // 页脚设置
    'site_contact_email',
    'site_contact_phone',
    'site_contact_hours',
    'site_social_wechat',
    'site_social_weibo',
    'site_social_qq',
    'site_social_bilibili',
]);

// 设置默认值
$heroBadge = $homeSettings['home_hero_badge'] ?: 'AI创作革命';
$heroTitle = $homeSettings['home_hero_title'] ?: 'AI智能创作平台';
$heroSubtitle = $homeSettings['home_hero_subtitle'] ?: '专为创作者打造的AI增效工具，辅助10000+作者在各大平台创作优质内容。通过尖端AI技术，让您的创作灵感无限延伸。';
$heroCtaPrimary = $homeSettings['home_hero_cta_primary'] ?: '立即免费试用';
$heroCtaSecondary = $homeSettings['home_hero_cta_secondary'] ?: '观看演示';

// 统计数据
$statUsers = $homeSettings['home_stat_users'] ?: ($stats['total_users'] ?? 10000);
$statNovels = $homeSettings['home_stat_novels'] ?: ($stats['total_novels'] ?? 50000);
$statWords = $homeSettings['home_stat_words'] ?: ($stats['total_words'] ?? 10000000);

// 页脚联系信息
$contactEmail = $homeSettings['site_contact_email'] ?: 'support@starrynight.com';
$contactPhone = $homeSettings['site_contact_phone'] ?: '400-888-8888';
$contactHours = $homeSettings['site_contact_hours'] ?: '工作日 9:00-18:00';

// 社交媒体链接
$socialWechat = $homeSettings['site_social_wechat'] ?: '#';
$socialWeibo = $homeSettings['site_social_weibo'] ?: '#';
$socialQq = $homeSettings['site_social_qq'] ?: '#';
$socialBilibili = $homeSettings['site_social_bilibili'] ?: '#';
?>
<!-- 企业官网首页 -->
<div class="enterprise-home">
    <!-- 导航栏 -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <?php if ($siteLogo): ?>
                    <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="brand-logo">
                <?php endif; ?>
                <span class="brand-name"><?= htmlspecialchars($siteName) ?></span>
            </div>
            <nav class="nav-menu">
                <a href="#features" class="nav-link">产品功能</a>
                <a href="#solutions" class="nav-link">解决方案</a>
                <a href="#showcase" class="nav-link">成功案例</a>
                <a href="#pricing" class="nav-link">价格方案</a>
                <a href="#about" class="nav-link">关于我们</a>
            </nav>
            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="/user_center" class="btn btn-outline">用户中心</a>
                    <a href="/novel" class="btn btn-primary">进入创作工作台</a>
                <?php else: ?>
                    <a href="/login" class="btn btn-outline">登录</a>
                    <a href="/register" class="btn btn-primary">免费试用</a>
                <?php endif; ?>
            </div>
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="打开菜单">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <!-- 移动端菜单 -->
        <nav class="mobile-menu" id="mobileMenu">
            <a href="#features" class="nav-link">产品功能</a>
            <a href="#solutions" class="nav-link">解决方案</a>
            <a href="#showcase" class="nav-link">成功案例</a>
            <a href="#pricing" class="nav-link">价格方案</a>
            <a href="#about" class="nav-link">关于我们</a>
            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="/user_center" class="btn btn-outline">用户中心</a>
                    <a href="/novel" class="btn btn-primary">进入创作工作台</a>
                <?php else: ?>
                    <a href="/login" class="btn btn-outline">登录</a>
                    <a href="/register" class="btn btn-primary">免费试用</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- 英雄区域 -->
    <section class="hero-section<?= $isFestive ? ' festive-season' : '' ?>">
        <!-- 光晕效果 -->
        <div class="hero-glow hero-glow-1"></div>
        <div class="hero-glow hero-glow-2"></div>
        
        <!-- 粒子效果 -->
        <div class="hero-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-sparkles"></i>
                    <span><?= htmlspecialchars($heroBadge) ?></span>
                </div>
                <h1 class="hero-title">
                    <span class="gradient-text"><?= htmlspecialchars($siteName) ?></span>
                    <br><?= htmlspecialchars($heroTitle) ?>
                </h1>
                <p class="hero-subtitle"><?= htmlspecialchars($heroSubtitle) ?></p>
                <div class="hero-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="/novel_creation" class="btn btn-primary btn-large">
                            <i class="fas fa-rocket"></i>
                            开始创作
                        </a>
                    <?php else: ?>
                        <a href="/register" class="btn btn-primary btn-large">
                            <i class="fas fa-rocket"></i>
                            <?= htmlspecialchars($heroCtaPrimary) ?>
                        </a>
                    <?php endif; ?>
                    <a href="#demo" class="btn btn-outline btn-large">
                        <i class="fas fa-play-circle"></i>
                        <?= htmlspecialchars($heroCtaSecondary) ?>
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($statUsers) ?>+</span>
                        <span class="stat-label">活跃创作者</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= number_format($statNovels) ?>+</span>
                        <span class="stat-label">创作作品</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php 
                            if ($statWords >= 10000) {
                                echo number_format($statWords / 10000, 1) . '万+';
                            } else {
                                echo number_format($statWords) . '+';
                            }
                        ?></span>
                        <span class="stat-label">生成字数</span>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="floating-elements">
                    <div class="floating-element element-1">
                        <i class="fas fa-book"></i>
                        <span>小说创作</span>
                    </div>
                    <div class="floating-element element-2">
                        <i class="fas fa-music"></i>
                        <span>AI音乐</span>
                    </div>
                    <div class="floating-element element-3">
                        <i class="fas fa-film"></i>
                        <span>动画制作</span>
                    </div>
                    <div class="floating-element element-4">
                        <i class="fas fa-robot"></i>
                        <span>AI助手</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 产品功能 -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">核心功能</div>
                <h2 class="section-title">强大的AI创作能力</h2>
                <p class="section-subtitle">我们的AI技术为您提供全方位的创作支持，让创作更高效、更智能</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI智能写作</h3>
                    <p class="feature-description">让AI成为您的创作助手，通过尖端AI技术，轻松创建高质量内容，提升创作效率10倍以上</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>智能续写</li>
                        <li><i class="fas fa-check"></i>自动润色</li>
                        <li><i class="fas fa-check"></i>风格模仿</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </div>
                    <h3 class="feature-title">智能扩写润色</h3>
                    <p class="feature-description">将简短内容变成精彩篇章，智能扩展和丰富内容，提升文字表现力和可读性</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>内容扩写</li>
                        <li><i class="fas fa-check"></i>语言润色</li>
                        <li><i class="fas fa-check"></i>逻辑优化</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-pen"></i>
                    </div>
                    <h3 class="feature-title">AI续写</h3>
                    <p class="feature-description">灵感不断，文思如泉涌，当您遇到写作瓶颈时，AI续写功能无缝接续思路</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>情节延续</li>
                        <li><i class="fas fa-check"></i>角色保持</li>
                        <li><i class="fas fa-check"></i>风格一致</li>
                    </ul>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3 class="feature-title">AI拆书</h3>
                    <p class="feature-description">深入解析文学结构与精髓，智能分析作品结构、情节和写作技巧，助力创作提升</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i>结构分析</li>
                        <li><i class="fas fa-check"></i>技巧提取</li>
                        <li><i class="fas fa-check"></i>仿写指导</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 创作工具箱 -->
    <section class="tools-section" id="solutions">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">创作工具</div>
                <h2 class="section-title">全方位创作工具集</h2>
                <p class="section-subtitle">为创作者提供丰富多样的专业辅助工具，满足各种创作需求</p>
            </div>
            
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-heading"></i>
                    </div>
                    <h3 class="tool-title">书名生成器</h3>
                    <p class="tool-description">创造吸引眼球的标题，让读者第一眼就被吸引，提升作品点击率</p>
                    <div class="tool-features">
                        <span class="tool-tag">多种风格</span>
                        <span class="tool-tag">SEO优化</span>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-align-left"></i>
                    </div>
                    <h3 class="tool-title">简介生成器</h3>
                    <p class="tool-description">简洁有力的内容概述，让读者迫不及待想了解更多内容</p>
                    <div class="tool-features">
                        <span class="tool-tag">精准概括</span>
                        <span class="tool-tag">吸引眼球</span>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <h3 class="tool-title">大纲生成器</h3>
                    <p class="tool-description">构建完整的故事脉络，让创作有条不紊地进行，避免情节混乱</p>
                    <div class="tool-features">
                        <span class="tool-tag">结构清晰</span>
                        <span class="tool-tag">逻辑严密</span>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="tool-title">人设生成器</h3>
                    <p class="tool-description">打造立体饱满的角色形象，让角色栩栩如生，深入人心</p>
                    <div class="tool-features">
                        <span class="tool-tag">性格丰富</span>
                        <span class="tool-tag">背景完整</span>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3 class="tool-title">金手指生成器</h3>
                    <p class="tool-description">设计出人意料的情节转折，让故事充满惊喜和吸引力</p>
                    <div class="tool-features">
                        <span class="tool-tag">创意无限</span>
                        <span class="tool-tag">合理设定</span>
                    </div>
                </div>
                
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="tool-title">黄金开篇</h3>
                    <p class="tool-description">创作引人入胜的开头，一开篇就抓住读者的心和注意力</p>
                    <div class="tool-features">
                        <span class="tool-tag">瞬间吸引</span>
                        <span class="tool-tag">悬念设置</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 成功案例 -->
    <section class="showcase-section" id="showcase">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">成功案例</div>
                <h2 class="section-title">创作者的成功故事</h2>
                <p class="section-subtitle">以下作品由<?= htmlspecialchars($siteName) ?>辅助创作，在各大平台获得优异成绩</p>
            </div>
            
            <div class="showcase-grid">
                <div class="showcase-card">
                    <div class="showcase-cover">
                        <div class="cover-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="cover-badge">热门</div>
                    </div>
                    <div class="showcase-info">
                        <h3 class="showcase-title">每天属性翻倍，校花倒追求我生娃</h3>
                        <div class="showcase-meta">
                            <span class="genre">都市脑洞</span>
                            <span class="stats">69万字 · 连载中</span>
                        </div>
                        <div class="showcase-rank">阅读榜 第21名</div>
                        <div class="showcase-desc">通过AI续写功能，作者日更万字，情节跌宕起伏，读者追更热情高涨。</div>
                    </div>
                </div>
                
                <div class="showcase-card">
                    <div class="showcase-cover">
                        <div class="cover-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="cover-badge">新书</div>
                    </div>
                    <div class="showcase-info">
                        <h3 class="showcase-title">序列海洋求生：开局觉醒神级序列</h3>
                        <div class="showcase-meta">
                            <span class="genre">科幻末世</span>
                            <span class="stats">70.8万字 · 连载中</span>
                        </div>
                        <div class="showcase-rank">新书榜 第1名</div>
                        <div class="showcase-desc">利用AI大纲生成器构建完整世界观，情节紧凑，逻辑严密。</div>
                    </div>
                </div>
                
                <div class="showcase-card">
                    <div class="showcase-cover">
                        <div class="cover-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="showcase-info">
                        <h3 class="showcase-title">给儿子请的校花家教变成了他后妈</h3>
                        <div class="showcase-meta">
                            <span class="genre">都市日常</span>
                            <span class="stats">40.6万字 · 连载中</span>
                        </div>
                        <div class="showcase-rank">新书榜 第18名</div>
                        <div class="showcase-desc">AI角色生成器帮助塑造鲜活人物形象，角色性格饱满立体。</div>
                    </div>
                </div>
                
                <div class="showcase-card">
                    <div class="showcase-cover">
                        <div class="cover-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="cover-badge">完结</div>
                    </div>
                    <div class="showcase-info">
                        <h3 class="showcase-title">我只想躺平，可老祖抗帝兵抢亲！</h3>
                        <div class="showcase-meta">
                            <span class="genre">玄幻脑洞</span>
                            <span class="stats">51.9万字 · 已完结</span>
                        </div>
                        <div class="showcase-rank">新书榜 第18名</div>
                        <div class="showcase-desc">黄金开篇功能助力创作精彩开头，作品上架即火爆。</div>
                    </div>
                </div>
            </div>
            
            <div class="showcase-more">
                <a href="/ranking" class="btn btn-outline">查看更多成功案例</a>
            </div>
        </div>
    </section>

    <!-- 价格方案 -->
    <section class="pricing-section" id="pricing">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">价格方案</div>
                <h2 class="section-title">选择适合您的套餐</h2>
                <p class="section-subtitle">灵活的定价方案，满足不同创作需求，助力创作事业腾飞</p>
            </div>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="pricing-title">免费版</h3>
                        <div class="pricing-price">
                            <span class="price">¥0</span>
                            <span class="period">/月</span>
                        </div>
                        <p class="pricing-desc">适合个人创作者体验</p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> 每日1000字创作额度</li>
                        <li><i class="fas fa-check"></i> 基础创作工具</li>
                        <li><i class="fas fa-check"></i> 3个作品项目</li>
                        <li><i class="fas fa-times"></i> 高级AI模型</li>
                        <li><i class="fas fa-times"></i> 优先客服支持</li>
                    </ul>
                    <a href="/register" class="btn btn-outline">立即注册</a>
                </div>
                
                <div class="pricing-card featured">
                    <div class="pricing-badge">最受欢迎</div>
                    <div class="pricing-header">
                        <h3 class="pricing-title">专业版</h3>
                        <div class="pricing-price">
                            <span class="price">¥29</span>
                            <span class="period">/月</span>
                        </div>
                        <p class="pricing-desc">适合专业创作者</p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> 每日10000字创作额度</li>
                        <li><i class="fas fa-check"></i> 全部创作工具</li>
                        <li><i class="fas fa-check"></i> 50个作品项目</li>
                        <li><i class="fas fa-check"></i> 高级AI模型</li>
                        <li><i class="fas fa-check"></i> 优先客服支持</li>
                    </ul>
                    <a href="/membership" class="btn btn-primary">立即升级</a>
                </div>
                
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="pricing-title">企业版</h3>
                        <div class="pricing-price">
                            <span class="price">¥99</span>
                            <span class="period">/月</span>
                        </div>
                        <p class="pricing-desc">适合团队和企业</p>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> 无限创作额度</li>
                        <li><i class="fas fa-check"></i> 全部创作工具</li>
                        <li><i class="fas fa-check"></i> 无限作品项目</li>
                        <li><i class="fas fa-check"></i> 顶级AI模型</li>
                        <li><i class="fas fa-check"></i> 专属客服支持</li>
                    </ul>
                    <a href="/membership" class="btn btn-outline">联系客服</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA区域 -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <div class="cta-badge">限时优惠</div>
                <h2 class="cta-title">准备好开启您的创作之旅了吗？</h2>
                <p class="cta-subtitle">加入我们，探索AI写作的无限可能，让您的文字焕发生机。现在注册即享7天免费试用！</p>
                <div class="cta-actions">
                    <a href="/register" class="btn btn-primary btn-large">免费注册</a>
                    <a href="#demo" class="btn btn-outline btn-large">预约演示</a>
                </div>
                <div class="cta-features">
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>无需信用卡</span>
                    </div>
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>随时取消</span>
                    </div>
                    <div class="cta-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>专业技术支持</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 用户评价区域 -->
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">用户评价</div>
                <h2 class="section-title">创作者们怎么说</h2>
                <p class="section-subtitle">来自真实用户的反馈，见证AI创作带来的改变</p>
            </div>
            
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-content">"使用<?= htmlspecialchars($siteName) ?>后，我的创作效率提升了3倍！AI续写功能太强大了，完全理解我的写作风格。"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <span class="author-name">云中鹤</span>
                            <span class="author-title">网络小说作家</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-content">"大纲生成器帮我解决了卡文问题，现在创作思路清晰多了。强烈推荐给所有创作者！"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <span class="author-name">墨染青衫</span>
                            <span class="author-title">签约作者</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-content">"人设生成器太棒了！帮我塑造了非常立体的角色，读者都说我的角色很有魅力。"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <span class="author-name">星河漫步</span>
                            <span class="author-title">新人作者</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 合作伙伴区域 -->
    <section class="partners-section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">合作伙伴</div>
                <h2 class="section-title">值得信赖的创作平台</h2>
                <p class="section-subtitle">与行业领先平台合作，为创作者提供更多机会</p>
            </div>
            
            <div class="partners-grid">
                <div class="partner-item">
                    <i class="fas fa-book-open"></i>
                    <span>起点中文网</span>
                </div>
                <div class="partner-item">
                    <i class="fas fa-feather-alt"></i>
                    <span>晋江文学城</span>
                </div>
                <div class="partner-item">
                    <i class="fas fa-scroll"></i>
                    <span>番茄小说</span>
                </div>
                <div class="partner-item">
                    <i class="fas fa-book"></i>
                    <span>七猫小说</span>
                </div>
                <div class="partner-item">
                    <i class="fas fa-pen-fancy"></i>
                    <span>纵横中文网</span>
                </div>
                <div class="partner-item">
                    <i class="fas fa-newspaper"></i>
                    <span>17K小说网</span>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ区域 -->
    <section class="faq-section" id="faq">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">常见问题</div>
                <h2 class="section-title">您可能想了解的问题</h2>
                <p class="section-subtitle">快速解答您的疑惑，让您更安心地开始创作之旅</p>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>如何开始使用AI创作？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>只需注册账号，选择您需要的创作工具，输入您的创意想法，AI就会帮您生成高质量的内容。我们提供详细的使用教程，让您快速上手。</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>免费版和付费版有什么区别？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>免费版每日提供1000字创作额度，可使用基础创作工具。付费版享有更高的创作额度、全部创作工具、高级AI模型和优先客服支持等特权。</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>AI生成的内容版权归谁？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>您使用我们平台生成的内容，版权归您所有。我们不会对您的创作内容主张任何权利，您可以自由使用和发布。</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>如何保障我的创作隐私？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>我们采用银行级加密技术保护您的数据，所有创作内容仅您可见。我们承诺不会泄露或分享您的创作内容给第三方。</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>支持哪些类型的创作？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>我们支持网络小说、短篇故事、剧本创作、文案写作等多种类型。无论是玄幻、都市、科幻还是言情，AI都能帮您创作精彩内容。</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>可以随时取消订阅吗？</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>是的，您可以随时取消订阅，无需支付任何违约金。取消后，您仍可使用服务直到当前计费周期结束。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <?php if ($siteLogo): ?>
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
                        <?php endif; ?>
                        <span><?= htmlspecialchars($siteName) ?></span>
                    </div>
                    <p class="footer-desc">专为创作者打造的AI增效工具，让您的创作灵感无限延伸。</p>
                    <div class="footer-social">
                        <a href="<?= htmlspecialchars($socialWechat) ?>" class="social-link" title="微信">
                            <i class="fab fa-weixin"></i>
                        </a>
                        <a href="<?= htmlspecialchars($socialWeibo) ?>" class="social-link" title="微博">
                            <i class="fab fa-weibo"></i>
                        </a>
                        <a href="<?= htmlspecialchars($socialQq) ?>" class="social-link" title="QQ">
                            <i class="fab fa-qq"></i>
                        </a>
                        <a href="<?= htmlspecialchars($socialBilibili) ?>" class="social-link" title="哔哩哔哩">
                            <i class="fab fa-bilibili"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h4>产品服务</h4>
                    <ul>
                        <li><a href="#features">AI写作</a></li>
                        <li><a href="#solutions">创作工具</a></li>
                        <li><a href="#pricing">会员套餐</a></li>
                        <li><a href="#">API接口</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>帮助支持</h4>
                    <ul>
                        <li><a href="#faq">常见问题</a></li>
                        <li><a href="#">使用教程</a></li>
                        <li><a href="#">联系客服</a></li>
                        <li><a href="#">意见反馈</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>关于我们</h4>
                    <ul>
                        <li><a href="#about">公司介绍</a></li>
                        <li><a href="#">加入我们</a></li>
                        <li><a href="#">合作伙伴</a></li>
                        <li><a href="#">新闻动态</a></li>
                    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>联系方式</h4>
                    <ul>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($contactEmail) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($contactPhone) ?></span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span><?= htmlspecialchars($contactHours) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. 保留所有权利。</p>
                </div>
                <div class="footer-legal">
                    <a href="#">服务条款</a>
                    <a href="#">隐私政策</a>
                    <a href="#">Cookie政策</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- 返回顶部按钮 -->
    <button class="back-to-top" id="backToTop" title="返回顶部">
        <i class="fas fa-arrow-up"></i>
    </button>
</div>

<!-- 首页交互脚本 -->
<script>
(function() {
    'use strict';
    
    // 移动端菜单切换
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            const icon = this.querySelector('i');
            if (mobileMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // 点击菜单链接后关闭菜单
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    }
    
    // 返回顶部按钮
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // FAQ折叠展开
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        if (question) {
            question.addEventListener('click', function() {
                // 关闭其他打开的FAQ
                faqItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('active')) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // 切换当前FAQ
                item.classList.toggle('active');
            });
        }
    });
    
    // 滚动动画
    const animateElements = document.querySelectorAll('.feature-card, .tool-card, .showcase-card, .testimonial-card, .pricing-card, .partner-item, .faq-item');
    
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    animateElements.forEach(el => {
        el.classList.add('animate-on-scroll');
        observer.observe(el);
    });
    
    // 导航栏引用
    const navbar = document.querySelector('.navbar');
    
    // 平滑滚动到锚点（考虑导航高度）
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href !== '#' && href.length > 1) {
                const target = document.querySelector(href);
                
                if (target && navbar) {
                    e.preventDefault();
                    
                    const navbarHeight = navbar.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.scrollY - navbarHeight - 12;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // 导航栏滚动效果（通过类控制，避免内联样式覆盖玻璃效果）
    if (navbar) {
        const handleNavbarScroll = () => {
        if (window.scrollY > 100) {
                navbar.classList.add('navbar-scrolled');
        } else {
                navbar.classList.remove('navbar-scrolled');
            }
        };
        
        handleNavbarScroll();
        window.addEventListener('scroll', handleNavbarScroll);
    }
    
    // 顶部导航滚动联动高亮（Scroll Spy）
    const sectionLinks = Array.from(document.querySelectorAll('.navbar .nav-link[href^="#"]'));
    const sectionMap = sectionLinks
        .map(link => {
            const id = link.getAttribute('href');
            const section = id ? document.querySelector(id) : null;
            return section ? { link, section } : null;
        })
        .filter(Boolean);
    
    const updateActiveNav = () => {
        if (!navbar || sectionMap.length === 0) return;
        
        const offset = (navbar.offsetHeight || 0) + 40;
        const scrollPos = window.scrollY + offset;
        let activeLink = null;
        
        sectionMap.forEach(({ link, section }) => {
            const top = section.offsetTop;
            const bottom = top + section.offsetHeight;
            if (scrollPos >= top && scrollPos < bottom) {
                activeLink = link;
            }
        });
        
        sectionLinks.forEach(l => l.classList.remove('nav-link-active'));
        if (activeLink) {
            activeLink.classList.add('nav-link-active');
        }
    };
    
    window.addEventListener('scroll', updateActiveNav);
    window.addEventListener('load', updateActiveNav);
    
    // 数字动画（统计数字）
    const statNumbers = document.querySelectorAll('.stat-number');
    
    const animateNumber = (element) => {
        const text = element.textContent;
        const hasPlus = text.includes('+');
        const hasWan = text.includes('万');
        
        let num = parseFloat(text.replace(/[^0-9.]/g, ''));
        let targetNum = num;
        let currentNum = 0;
        let duration = 2000;
        let startTime = null;
        
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            let progress = Math.min((timestamp - startTime) / duration, 1);
            
            // 使用缓动函数
            progress = 1 - Math.pow(1 - progress, 3);
            
            currentNum = Math.floor(progress * targetNum);
            
            let displayText = currentNum.toLocaleString();
            if (hasWan) {
                displayText = (currentNum / 10000).toFixed(1) + '万';
            }
            if (hasPlus) displayText += '+';
            
            element.textContent = displayText;
            
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        
        requestAnimationFrame(step);
    };
    
    // 观察统计数字
    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateNumber(entry.target);
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    statNumbers.forEach(stat => {
        statsObserver.observe(stat);
    });
})();
</script>
