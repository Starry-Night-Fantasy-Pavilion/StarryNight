<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<div class="notice-nav" style="margin-bottom:16px;">
    <a class="notice-tab <?= ($currentPage ?? '') === 'notice-list' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/notice/list">通知栏</a>
    <a class="notice-tab <?= ($currentPage ?? '') === 'feedback-list' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/feedback/list">用户反馈</a>
    <a class="notice-tab <?= ($currentPage ?? '') === 'announcement-list' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/announcement/list">公告管理</a>
    <a class="notice-tab" href="/<?= $adminPrefix ?>/announcement/categories">公告分类</a>
</div>
<style>
.notice-nav { display: flex; flex-wrap: wrap; gap: 8px; }
.notice-tab { padding: 8px 14px; border-radius: 6px; background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px; }
.notice-tab:hover { background: rgba(255,255,255,0.1); color: #fff; }
.notice-tab.active { background: var(--neon-blue, #0ea5e9); color: #fff; }
</style>
