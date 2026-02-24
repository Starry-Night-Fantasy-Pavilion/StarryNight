<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">用户中心</h1>
        <p class="dashboard-subtitle-v2">欢迎回来，<?= $h($user['nickname'] ?? $user['username']) ?></p>
    </div>
    <div class="dashboard-grid-v2">
        <a href="/novel" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-novel">
                <?= render_icon('book', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">我的小说</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">作品数量</span>
                        <span class="stat-value"><?= number_format($stats['novels'] ?? 0) ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">总字数</span>
                        <span class="stat-value"><?php 
                            $words = $stats['novel_words'] ?? 0;
                            if ($words >= 10000) {
                                echo number_format($words / 10000, 1) . '万';
                            } else {
                                echo number_format($words);
                            }
                        ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/novel_creation" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-novel">
                <?= render_icon('book', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">小说创作工具</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">使用AI辅助创作</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/ai_music" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-music">
                <?= render_icon('music', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">AI音乐</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">作品数量</span>
                        <span class="stat-value"><?= number_format($stats['music'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/anime_production" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-anime">
                <?= render_icon('anime', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">动画制作</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">项目数量</span>
                        <span class="stat-value"><?= number_format($stats['anime'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/membership" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-user">
                <?= render_icon('users', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">会员管理</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">会员等级</span>
                        <span class="stat-value"><?= $h($membership['level_name'] ?? '普通用户') ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">余额</span>
                        <span class="stat-value"><?= number_format($wallet['balance'] ?? 0, 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/storage" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-storage">
                <?= render_icon('storage', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">存储管理</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">云存储空间</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>

        <a href="/user_center/starry_night_config" class="dashboard-card-v2">
            <div class="card-icon-v2 bg-plugins">
                <?= render_icon('plugins', ['width' => '32', 'height' => '32']) ?>
            </div>
            <div class="card-content-v2">
                <h3 class="card-title-v2">星夜创作引擎</h3>
                <div class="card-stats-row">
                    <div class="stat-item">
                        <span class="stat-label">引擎配置</span>
                    </div>
                </div>
            </div>
            <div class="card-arrow-v2">
                <?= render_icon('arrow-right', ['width' => '20', 'height' => '20']) ?>
            </div>
        </a>
    </div>
</div>
