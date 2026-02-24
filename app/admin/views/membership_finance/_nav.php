<?php
/** @var string $currentPage */
$adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
?>
<link rel="stylesheet" href="/static/admin/css/membership-finance.css?v=<?= time() ?>">

<div class="finance-nav">
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-levels' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/membership-levels">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
        </svg>
        会员等级
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-packages' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/coin-packages">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v12M9 9h6M9 15h6"/>
        </svg>
        充值套餐
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-orders' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/orders">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        充值记录
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-coin-spend' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/coin-spend-records">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        星夜币消耗
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-coupons' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/coupons">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 5H3a2 2 0 0 0-2 2v3a2 2 0 0 1 2 2 2 2 0 0 1-2 2v3a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2v-3a2 2 0 0 1-2-2 2 2 0 0 1 2-2V7a2 2 0 0 0-2-2z"/>
            <path d="M6 12h4M8 10v4"/>
        </svg>
        优惠券
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-activities' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/activities">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        活动配置
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-promotion' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/promotion-links">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
        </svg>
        推广链接
    </a>
    <a class="finance-tab <?= ($currentPage ?? '') === 'finance-messages' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/finance/site-messages">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        站内信
    </a>
</div>
