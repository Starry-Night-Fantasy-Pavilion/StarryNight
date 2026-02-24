<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<link rel="stylesheet" href="/static/admin/css/system-config.css?v=<?= time() ?>">

<div class="system-nav">
    <a class="system-tab <?= ($currentPage ?? '') === 'system-basic' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/basic-settings">基础设置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-home' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/home-settings">首页设置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-register' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/register-settings">注册设置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-legal' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/legal-settings">协议设置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-storage' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/storage-config">存储配置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-roles' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/roles">角色管理</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-logs' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/operation-logs">操作日志</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-logs' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/login-logs">登录日志</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-logs' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/exception-logs">异常日志</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-security' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/security">安全设置</a>
    <a class="system-tab <?= ($currentPage ?? '') === 'system-starry-night' ? 'active' : '' ?>" href="/<?= $adminPrefix ?>/system/starry-night-engine">星夜创作引擎</a>
</div>
