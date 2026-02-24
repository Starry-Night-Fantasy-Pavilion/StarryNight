<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员中心 - 星夜阁</title>
    <?php use app\config\FrontendConfig; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(FrontendConfig::getThemeCssUrl('modules/membership.css')) ?>">
</head>
<body>
    <div class="membership-container">
        <!-- 侧边栏 -->
        <aside class="membership-sidebar">
            <div class="user-info">
                <div class="avatar">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= $user['avatar'] ?>" alt="<?= htmlspecialchars($user['nickname']) ?>">
                    <?php else: ?>
                        <div class="default-avatar"><?= substr(htmlspecialchars($user['nickname']), 0, 1) ?></div>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h3><?= htmlspecialchars($user['nickname']) ?></h3>
                    <p class="user-id">ID: <?= $user['id'] ?></p>
                    <?php if ($membership): ?>
                        <div class="membership-badge <?= $membership['type'] ?>">
                            <?= $membership['type_name'] ?>
                        </div>
                    <?php else: ?>
                        <div class="membership-badge free">普通用户</div>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li><a href="/membership" class="active">会员中心</a></li>
                    <li><a href="/membership/packages">会员套餐</a></li>
                    <li><a href="/membership/recharge">充值中心</a></li>
                    <li><a href="/membership/orders">我的订单</a></li>
                    <li><a href="/membership/token-records">消费记录</a></li>
                </ul>
            </nav>

            <!-- 快速操作 -->
            <div class="quick-actions">
                <h4>快速操作</h4>
                <div class="action-buttons">
                    <?php if (!$membership): ?>
                        <a href="/membership/packages" class="btn btn-primary">升级会员</a>
                    <?php endif; ?>
                    <a href="/membership/recharge" class="btn btn-secondary">充值星夜币</a>
                </div>
            </div>
        </aside>

        <!-- 主内容区 -->
        <main class="membership-main">
            <!-- 会员状态卡片 -->
            <section class="membership-status">
                <h2>会员状态</h2>
                <div class="status-cards">
                    <?php if ($membership): ?>
                        <div class="status-card active">
                            <div class="card-icon">
                                <i class="icon-vip"></i>
                            </div>
                            <div class="card-content">
                                <h3><?= $membership['type_name'] ?></h3>
                                <p class="status">有效</p>
                                <?php if (!$membership['is_lifetime']): ?>
                                    <p class="expire-date">
                                        到期时间：<?= date('Y-m-d', strtotime($membership['end_time'])) ?>
                                    </p>
                                    <p class="days-remaining">
                                        剩余 <?= $membership['days_remaining'] ?> 天
                                    </p>
                                <?php else: ?>
                                    <p class="lifetime">终身有效</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="status-card inactive">
                            <div class="card-icon">
                                <i class="icon-user"></i>
                            </div>
                            <div class="card-content">
                                <h3>普通用户</h3>
                                <p class="status">未开通会员</p>
                                <p class="upgrade-tip">升级会员享受更多权益</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 星夜币余额 -->
            <section class="token-balance">
                <h2>星夜币余额</h2>
                <div class="balance-card">
                    <div class="balance-amount">
                        <span class="currency">⭐</span>
                        <span class="amount"><?= number_format($tokenBalance['balance']) ?></span>
                    </div>
                    <div class="balance-stats">
                        <div class="stat-item">
                            <span class="label">累计充值</span>
                            <span class="value"><?= number_format($tokenBalance['total_recharged']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="label">累计消费</span>
                            <span class="value"><?= number_format($tokenBalance['total_consumed']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="label">获得赠送</span>
                            <span class="value"><?= number_format($tokenBalance['total_bonus']) ?></span>
                        </div>
                    </div>
                    <div class="balance-actions">
                        <a href="/membership/recharge" class="btn btn-primary">立即充值</a>
                        <a href="/membership/token-records" class="btn btn-secondary">查看记录</a>
                    </div>
                </div>
            </section>

            <!-- 使用限制 -->
            <section class="usage-limits">
                <h2>使用限制</h2>
                <div class="limits-grid">
                    <?php foreach ($limitStatus as $key => $limit): ?>
                        <div class="limit-item <?= $limit['is_unlimited'] ? 'unlimited' : ($limit['percentage'] >= 80 ? 'warning' : '') ?>">
                            <div class="limit-header">
                                <h4><?= $this->getLimitName($key) ?></h4>
                                <?php if (!$limit['is_unlimited']): ?>
                                    <span class="limit-text"><?= $limit['current'] ?> / <?= $limit['limit'] ?></span>
                                <?php else: ?>
                                    <span class="limit-text">无限制</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$limit['is_unlimited']): ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min(100, $limit['percentage']) ?>%"></div>
                                </div>
                            <?php endif; ?>
                            <div class="limit-footer">
                                <?php if (!$limit['is_unlimited']): ?>
                                    <span class="remaining">剩余 <?= $limit['remaining'] ?></span>
                                <?php else: ?>
                                    <span class="unlimited-text">会员无限制</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 会员权益 -->
            <?php if ($vipBenefits): ?>
                <section class="vip-benefits">
                    <h2>会员权益</h2>
                    <div class="benefits-grid">
                        <?php foreach ($vipBenefits as $benefit): ?>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="icon-<?= $benefit['benefit_type'] ?>"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4><?= htmlspecialchars($benefit['benefit_name']) ?></h4>
                                    <p><?= htmlspecialchars($benefit['description']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- 推荐套餐 -->
            <?php if ($recommendedPackages): ?>
                <section class="recommended-packages">
                    <h2>推荐套餐</h2>
                    <div class="packages-grid">
                        <?php foreach ($recommendedPackages as $package): ?>
                            <div class="package-card <?= $package['is_recommended'] ? 'recommended' : '' ?>">
                                <?php if ($package['badge']): ?>
                                    <div class="package-badge"><?= htmlspecialchars($package['badge']) ?></div>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($package['name']) ?></h3>
                                <div class="package-price">
                                    <?php if ($package['discount']): ?>
                                        <span class="original-price">¥<?= $package['original_price'] ?></span>
                                        <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                        <span class="discount-badge"><?= $package['discount'] ?></span>
                                    <?php else: ?>
                                        <span class="price">¥<?= $package['actual_price'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="package-features">
                                    <?php if ($package['features_array']): ?>
                                        <ul>
                                            <?php foreach ($package['features_array'] as $feature): ?>
                                                <li><?= htmlspecialchars($feature) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="package-actions">
                                    <a href="/membership/packages" class="btn btn-outline">查看详情</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- 推荐充值 -->
            <?php if ($recommendedRechargePackages): ?>
                <section class="recommended-recharge">
                    <h2>推荐充值</h2>
                    <div class="recharge-grid">
                        <?php foreach ($recommendedRechargePackages as $package): ?>
                            <div class="recharge-card <?= $package['is_hot'] ? 'hot' : '' ?>">
                                <?php if ($package['badge']): ?>
                                    <div class="recharge-badge"><?= htmlspecialchars($package['badge']) ?></div>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($package['name']) ?></h3>
                                <div class="recharge-tokens">
                                    <span class="token-amount"><?= number_format($package['token_info']['total']) ?></span>
                                    <span class="token-label">星夜币</span>
                                </div>
                                <div class="recharge-price">
                                    <?php if ($package['discount']): ?>
                                        <span class="original-price">¥<?= $package['price'] ?></span>
                                        <span class="discount-price">¥<?= $package['actual_price'] ?></span>
                                        <span class="discount-badge"><?= $package['discount'] ?></span>
                                    <?php else: ?>
                                        <span class="price">¥<?= $package['actual_price'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($package['token_info']['bonus'] > 0 || $package['token_info']['vip_bonus'] > 0): ?>
                                    <div class="recharge-bonus">
                                        <?php if ($package['token_info']['bonus'] > 0): ?>
                                            <span class="bonus-item">赠送 <?= $package['token_info']['bonus'] ?> 星夜币</span>
                                        <?php endif; ?>
                                        <?php if ($package['token_info']['vip_bonus'] > 0): ?>
                                            <span class="bonus-item">会员额外赠送 <?= $package['token_info']['vip_bonus'] ?> 星夜币</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="recharge-actions">
                                    <a href="/membership/recharge" class="btn btn-primary">立即充值</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="<?= htmlspecialchars(FrontendConfig::getThemeJsUrl('modules/membership.js')) ?>"></script>
</body>
</html>

<?php
/**
 * 获取限制名称的中文显示
 */
function getLimitName($key) {
    $names = [
        'novels' => '作品数量',
        'prompts' => '自定义提示词',
        'agents' => '智能体数量',
        'daily_ai_generations' => '每日AI生成',
        'daily_words' => '每日字数',
        'monthly_words' => '每月字数'
    ];
    
    return $names[$key] ?? $key;
}
?>