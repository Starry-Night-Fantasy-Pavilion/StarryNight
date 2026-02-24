<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
?>
<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">运营数据管理中心</h1>
        <p class="dashboard-subtitle-v2">选择下方功能模块查看详细数据</p>
    </div>
    <div class="dashboard-grid-v2">
        <a href="/<?= $adminPrefix ?>/operations/new-user" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-user">
                <?= render_icon('users', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">用户管理</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总用户</span>
                        <span class="stat-value"><?= number_format($stats['users']['total_users'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">今日新增</span>
                        <span class="stat-value"><?= number_format($stats['users']['new_users_today'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/dau" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-dau">
                <?= render_icon('activity', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">活跃度 (DAU)</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">今日活跃</span>
                        <span class="stat-value"><?= number_format($stats['users']['dau'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">月活跃</span>
                        <span class="stat-value"><?= number_format($stats['users']['mau'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/coin-spend" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-coin">
                <?= render_icon('coins', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">星夜币消耗</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总消耗</span>
                        <span class="stat-value"><?= number_format($stats['coins']['total_spent'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/revenue" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-revenue">
                <?= render_icon('revenue', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">收入统计</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总收入</span>
                        <span class="stat-value">¥<?= number_format($stats['revenue']['total_revenue'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-novel" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-novel">
                <?= render_icon('book', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">小说内容</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总数</span>
                        <span class="stat-value"><?= number_format($stats['creation']['total_books'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-music" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-music">
                <?= render_icon('music', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">音乐内容</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总数</span>
                        <span class="stat-value"><?= number_format($stats['creation']['total_music'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/<?= $adminPrefix ?>/operations/new-anime" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-anime">
                <?= render_icon('anime', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">动漫内容</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">总数</span>
                        <span class="stat-value"><?= number_format($stats['creation']['total_anime'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>
    </div>
</div>
