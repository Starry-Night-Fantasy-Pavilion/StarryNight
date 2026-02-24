<?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$days = (int)($stats['meta']['days'] ?? 30);
$reportMetric = (string)($stats['meta']['report_metric'] ?? 'new_users');
$reportGroup = (string)($stats['meta']['report_group'] ?? 'day');

$formatInt = fn($v) => number_format((float)$v, 0, '.', ',');
$formatMoney = fn($v) => number_format((float)$v, 2, '.', ',');
$formatPercent = function ($v) use ($h) {
    if ($v === null || $v === '') return 'N/A';
    return $h(number_format((float)$v, 2, '.', ',')) . '%';
};
?>

<div class="dashboard-container">
    <!-- 顶部工具栏 - 全新设计 -->
    <div class="dashboard-header-section">
        <div class="dashboard-header-flex">
            <div class="dashboard-header-left">
                <div class="dashboard-title-wrapper">
                    <div class="dashboard-title">运营仪表盘</div>
                    <a href="/<?= trim((string) get_env('ADMIN_PATH', 'admin'), '/') ?>" class="dashboard-back-button" title="返回仪表盘">
                        <?= render_icon('arrow-left', ['width' => '16', 'height' => '16']) ?>
                        <span>返回</span>
                    </a>
                </div>
                <div class="dashboard-subtitle">
                    近 <?= $h($days) ?> 天 · DB <?= $h($stats['system']['db_status'] ?? '未知') ?> · Ping <?= $h(($stats['system']['db_ping_ms'] ?? null) === null ? 'N/A' : ($stats['system']['db_ping_ms'] . ' ms')) ?>
                </div>
            </div>
            <form method="get" class="dashboard-filter-form">
                <select name="days" class="dashboard-select">
                    <?php foreach ([7, 30, 90, 180] as $d): ?>
                        <option value="<?= $h($d) ?>" <?= $days === $d ? 'selected' : '' ?>>近<?= $h($d) ?>天</option>
                    <?php endforeach; ?>
                </select>
                <select name="report_metric" class="dashboard-select">
                    <option value="new_users" <?= $reportMetric === 'new_users' ? 'selected' : '' ?>>自定义：新增用户</option>
                    <option value="dau" <?= $reportMetric === 'dau' ? 'selected' : '' ?>>自定义：日活用户</option>
                    <option value="coin_spend" <?= $reportMetric === 'coin_spend' ? 'selected' : '' ?>>自定义：星夜币消耗</option>
                    <option value="revenue" <?= $reportMetric === 'revenue' ? 'selected' : '' ?>>自定义：收入</option>
                    <option value="new_books" <?= $reportMetric === 'new_books' ? 'selected' : '' ?>>自定义：新增小说</option>
                    <option value="new_music" <?= $reportMetric === 'new_music' ? 'selected' : '' ?>>自定义：新增音乐</option>
                    <option value="new_anime" <?= $reportMetric === 'new_anime' ? 'selected' : '' ?>>自定义：新增动漫</option>
                </select>
                <select name="report_group" class="dashboard-select">
                    <option value="day" <?= $reportGroup === 'day' ? 'selected' : '' ?>>按日</option>
                    <option value="week" <?= $reportGroup === 'week' ? 'selected' : '' ?>>按周</option>
                    <option value="month" <?= $reportGroup === 'month' ? 'selected' : '' ?>>按月</option>
                </select>
                <button type="submit" class="dashboard-button">刷新</button>
            </form>
        </div>
    </div>

    <!-- 一、用户数据分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
            <div class="dashboard-section-header border-user">
                <div class="dashboard-section-indicator bg-user"></div>
                <h2 class="dashboard-section-title">用户数据</h2>
            </div>
            
            <div class="dashboard-grid-4-cols">
                <div class="dashboard-stat-card border-user bg-user-light">
                            <div class="dashboard-stat-label">总用户数</div>
                            <div class="dashboard-stat-value text-user"><?= $h($formatInt($stats['users']['total_users'] ?? 0)) ?></div>
                            <div class="dashboard-stat-sub">今日新增 <?= $h($formatInt($stats['users']['new_users_today'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-stat-card border-dau bg-dau-light">
                            <div class="dashboard-stat-label">日活用户 (DAU)</div>
                            <div class="dashboard-stat-value text-dau"><?= $h($formatInt($stats['users']['dau'] ?? 0)) ?></div>
                            <div class="dashboard-stat-sub">月活用户 (MAU) <?= $h($formatInt($stats['users']['mau'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-stat-card border-retention bg-retention-light">
                            <div class="dashboard-stat-label">用户留存率</div>
                            <div class="dashboard-stat-value text-retention"><?= $formatPercent($stats['users']['retention_7d'] ?? null) ?></div>
                            <div class="dashboard-stat-sub">7日留存 · 30日留存 <?= $formatPercent($stats['users']['retention_30d'] ?? null) ?></div>
                        </div>
                        <div class="dashboard-stat-card border-default bg-default-light">
                            <div class="dashboard-stat-label">有效会员</div>
                    <div class="dashboard-stat-value text-default"><?= $h($formatInt($stats['users']['active_memberships'] ?? 0)) ?></div>
                </div>
            </div>

            <div class="dashboard-chart-container">
                <h3 class="dashboard-chart-title">用户增长趋势</h3>
                <div class="dashboard-chart-wrapper">
                    <canvas id="userGrowthChart" class="dashboard-chart" data-growth="<?= $h(json_encode($stats['users']['growth_trend'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 二、创作数据分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
            <div class="dashboard-section-header border-creation">
                <div class="dashboard-section-indicator bg-creation"></div>
                <h2 class="dashboard-section-title">创作数据</h2>
            </div>

            <div class="dashboard-grid-3-cols">
                <div class="dashboard-stat-card-sm border-books bg-books-light">
                            <div class="dashboard-stat-label">小说总量</div>
                            <div class="dashboard-stat-value-sm text-books"><?= $h($formatInt($stats['creation']['total_books'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-stat-card-sm border-music bg-music-light">
                            <div class="dashboard-stat-label">音乐总量</div>
                            <div class="dashboard-stat-value-sm text-music"><?= $h($formatInt($stats['creation']['total_music'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-stat-card-sm border-anime bg-anime-light">
                            <div class="dashboard-stat-label">动漫总量</div>
                    <div class="dashboard-stat-value-sm text-anime"><?= $h($formatInt($stats['creation']['total_anime'] ?? 0)) ?></div>
                </div>
            </div>

            <div class="dashboard-grid-3-cols">
                <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">热门小说 Top 5</h3>
                            <?php $topBooks = array_slice($stats['creation']['top_books'] ?? [], 0, 5); ?>
                            <?php if (!empty($topBooks)): ?>
                                <?php foreach ($topBooks as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h($row['title'] ?? '-') ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatInt($row['views'] ?? 0)) ?> 阅读</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>

                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">热门音乐 Top 5</h3>
                            <?php $topMusic = array_slice($stats['creation']['top_music'] ?? [], 0, 5); ?>
                            <?php if (!empty($topMusic)): ?>
                                <?php foreach ($topMusic as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h(($row['title'] ?? '-') . (isset($row['artist']) && $row['artist'] !== '' ? ' · ' . $row['artist'] : '')) ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatInt($row['plays'] ?? 0)) ?> 播放</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>

                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">热门动漫 Top 5</h3>
                            <?php $topAnime = array_slice($stats['creation']['top_anime'] ?? [], 0, 5); ?>
                            <?php if (!empty($topAnime)): ?>
                                <?php foreach ($topAnime as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h($row['title'] ?? '-') ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatInt($row['views'] ?? 0)) ?> 观看</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
    </div>

    <!-- 三、星夜币消耗分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
                    <div class="dashboard-section-header border-coins">
                        <div class="dashboard-section-indicator bg-coins"></div>
                        <h2 class="dashboard-section-title">星夜币消耗</h2>
                    </div>

                    <div class="dashboard-grid-3-cols">
                        <div class="dashboard-stat-card-lg border-coins bg-coins-light">
                            <div class="dashboard-stat-label">总消耗量</div>
                            <div class="dashboard-stat-value text-coins"><?= $h($formatInt($stats['coins']['total_spent'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">消耗分布 (Top 5)</h3>
                            <?php $spendByFeature = array_slice($stats['coins']['spend_by_feature'] ?? [], 0, 5); ?>
                            <?php if (!empty($spendByFeature)): ?>
                                <?php foreach ($spendByFeature as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h(ucfirst($row['feature'] ?? 'unknown')) ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatInt($row['total'] ?? 0)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">高消耗用户 Top 10</h3>
                            <?php $topSpenders = array_slice($stats['coins']['top_spenders'] ?? [], 0, 10); ?>
                            <?php if (!empty($topSpenders)): ?>
                                <div class="dashboard-list-scrollable">
                                    <?php foreach ($topSpenders as $row): ?>
                                        <div class="dashboard-list-item">
                                            <div class="dashboard-list-key"><?= $h($row['username'] ?? ('UID ' . ($row['user_id'] ?? '-'))) ?></div>
                                            <div class="dashboard-list-value"><?= $h($formatInt($row['total'] ?? 0)) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dashboard-chart-container">
                        <h3 class="dashboard-chart-title">星夜币消耗趋势</h3>
                        <div class="dashboard-chart-wrapper">
                            <canvas id="coinSpendTrendChart" class="dashboard-chart" data-trend="<?= $h(json_encode($stats['coins']['spend_trend'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"></canvas>
                        </div>
                    </div>
                </div>
    </div>

    <!-- 四、收入分析分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
                    <div class="dashboard-section-header border-revenue">
                        <div class="dashboard-section-indicator bg-revenue"></div>
                        <h2 class="dashboard-section-title">收入分析</h2>
                    </div>

                    <div class="dashboard-grid-3-cols">
                        <div class="dashboard-stat-card-lg border-revenue bg-revenue-light">
                            <div class="dashboard-stat-label">总收入 (CNY)</div>
                            <div class="dashboard-stat-value text-revenue"><?= $h($formatMoney($stats['revenue']['total_revenue'] ?? 0)) ?></div>
                        </div>
                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">充值渠道收入 (Top 5)</h3>
                            <?php $byGateway = array_slice($stats['revenue']['by_gateway'] ?? [], 0, 5); ?>
                            <?php if (!empty($byGateway)): ?>
                                <?php foreach ($byGateway as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h($row['gateway'] ?? 'unknown') ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatMoney($row['total'] ?? 0)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">会员等级贡献 (Top 5)</h3>
                            <?php $byLevel = array_slice($stats['revenue']['membership_by_level'] ?? [], 0, 5); ?>
                            <?php if (!empty($byLevel)): ?>
                                <?php foreach ($byLevel as $row): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h($row['level'] ?? 'unknown') ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatMoney($row['total'] ?? 0)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dashboard-list-empty">暂无数据</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dashboard-chart-container">
                        <h3 class="dashboard-chart-title">收入趋势</h3>
                        <div class="dashboard-chart-wrapper">
                            <canvas id="revenueTrendChart" class="dashboard-chart" data-trend="<?= $h(json_encode($stats['revenue']['trend'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"></canvas>
                        </div>
                    </div>
                </div>
    </div>

    <!-- 五、系统健康状态分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
                    <div class="dashboard-section-header border-system">
                        <div class="dashboard-section-indicator bg-system"></div>
                        <h2 class="dashboard-section-title">系统健康状态</h2>
                    </div>

                    <div class="dashboard-grid-5-cols">
                        <div class="dashboard-stat-card-sm border-default bg-default-light text-center">
                            <div class="dashboard-stat-label">服务器负载</div>
                            <div class="dashboard-stat-value-sm text-default">
                                <?= $h(($stats['system']['server_load'] ?? null) === null ? 'N/A' : number_format($stats['system']['server_load'], 2)) ?>
                            </div>
                        </div>
                        <div class="dashboard-stat-card-sm border-default bg-default-light text-center">
                            <div class="dashboard-stat-label">磁盘可用空间</div>
                            <div class="dashboard-stat-value-sm text-default">
                                <?= $h(($stats['system']['disk_free_gb'] ?? null) === null ? 'N/A' : number_format($stats['system']['disk_free_gb'], 2) . ' GB') ?>
                            </div>
                        </div>
                        <div class="dashboard-stat-card-sm border-default bg-default-light text-center">
                            <div class="dashboard-stat-label">消息队列积压</div>
                            <div class="dashboard-stat-value-sm text-default">
                                <?= $h(($stats['system']['queue_backlog'] ?? null) === null ? 'N/A' : $formatInt($stats['system']['queue_backlog'])) ?>
                            </div>
                        </div>
                        <div class="dashboard-stat-card-sm border-default bg-default-light text-center">
                            <div class="dashboard-stat-label">API成功率</div>
                            <div class="dashboard-stat-value-sm text-default">
                                <?= $h(($stats['system']['api_success_rate'] ?? null) === null ? 'N/A' : number_format($stats['system']['api_success_rate'], 2) . '%') ?>
                            </div>
                        </div>
                        <div class="dashboard-stat-card-sm border-default bg-default-light text-center">
                            <div class="dashboard-stat-label">API平均延迟</div>
                            <div class="dashboard-stat-value-sm text-default">
                                <?= $h(($stats['system']['api_avg_latency_ms'] ?? null) === null ? 'N/A' : number_format($stats['system']['api_avg_latency_ms'], 2) . ' ms') ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($stats['system']['ai_channel_status'])): ?>
                        <div class="dashboard-list-container">
                            <h3 class="dashboard-list-title">AI渠道状态</h3>
                            <div class="dashboard-ai-channel-container">
                                <?php foreach ($stats['system']['ai_channel_status'] as $channelName => $channelInfo): ?>
                                    <div class="dashboard-ai-channel-item <?= $channelInfo['enabled'] ? 'enabled' : 'disabled' ?>">
                                        <?= $h(ucfirst($channelName)) ?>: <?= $h($channelInfo['status']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
    </div>

    <!-- 六、待处理事项分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
                    <div class="dashboard-section-header border-pending">
                        <div class="dashboard-section-indicator bg-pending"></div>
                        <h2 class="dashboard-section-title">待处理事项</h2>
                        <div class="dashboard-pending-total">
                            总计: <?= $h($formatInt(($stats['pending']['audit'] ?? 0) + ($stats['pending']['feedback'] ?? 0) + ($stats['pending']['alerts'] ?? 0) + ($stats['pending']['marketing'] ?? 0))) ?>
                        </div>
                    </div>

                    <div class="dashboard-grid-responsive-cols">
                        <?php if ($stats['pending']['audit'] > 0): ?>
                            <div class="dashboard-list-container">
                                <div class="dashboard-list-header">
                                    <h3 class="dashboard-list-title">审核任务</h3>
                                    <span class="dashboard-list-count count-audit"><?= $h($formatInt($stats['pending']['audit'])) ?></span>
                                </div>
                                <?php foreach ($stats['pending']['details'] as $detail): ?>
                                    <div class="dashboard-list-item">
                                        <div class="dashboard-list-key"><?= $h($detail['type'] ?? '-') ?></div>
                                        <div class="dashboard-list-value"><?= $h($formatInt($detail['count'] ?? 0)) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($stats['pending']['feedback_details'])): ?>
                            <div class="dashboard-list-container">
                                <div class="dashboard-list-header">
                                    <h3 class="dashboard-list-title">用户反馈</h3>
                                    <span class="dashboard-list-count count-feedback"><?= $h($formatInt($stats['pending']['feedback'])) ?></span>
                                </div>
                                <div class="dashboard-list-scrollable">
                                    <?php foreach (array_slice($stats['pending']['feedback_details'], 0, 5) as $feedback): ?>
                                        <div class="dashboard-list-item">
                                            <div class="dashboard-list-key">
                                                <div><?= $h($feedback['title'] ?? '无标题') ?></div>
                                                <?php if (isset($feedback['created_at'])): ?>
                                                    <div class="dashboard-list-subtitle"><?= $h(date('Y-m-d H:i', strtotime($feedback['created_at']))) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($stats['pending']['alert_details'])): ?>
                            <div class="dashboard-list-container">
                                <div class="dashboard-list-header">
                                    <h3 class="dashboard-list-title">异常告警</h3>
                                    <span class="dashboard-list-count count-alerts"><?= $h($formatInt($stats['pending']['alerts'])) ?></span>
                                </div>
                                <div class="dashboard-list-scrollable">
                                    <?php foreach (array_slice($stats['pending']['alert_details'], 0, 5) as $alert): ?>
                                        <div class="dashboard-list-item">
                                            <div class="dashboard-list-key">
                                                <div>
                                                    <span class="dashboard-alert-severity-<?= $h($alert['severity'] ?? 'low') ?>">
                                                        [<?= $h(strtoupper($alert['severity'] ?? 'low')) ?>]
                                                    </span>
                                                    <?= $h($alert['message'] ?? $alert['type'] ?? '-') ?>
                                                </div>
                                                <?php if (isset($alert['created_at'])): ?>
                                                    <div class="dashboard-list-subtitle"><?= $h(date('Y-m-d H:i', strtotime($alert['created_at']))) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($stats['pending']['marketing_details'])): ?>
                            <div class="dashboard-list-container">
                                <div class="dashboard-list-header">
                                    <h3 class="dashboard-list-title">营销活动提醒</h3>
                                    <span class="dashboard-list-count count-marketing"><?= $h($formatInt($stats['pending']['marketing'])) ?></span>
                                </div>
                                <div class="dashboard-list-scrollable">
                                    <?php foreach (array_slice($stats['pending']['marketing_details'], 0, 5) as $campaign): ?>
                                        <div class="dashboard-list-item">
                                            <div class="dashboard-list-key">
                                                <div><?= $h($campaign['name'] ?? '-') ?></div>
                                                <?php if (isset($campaign['start_date'])): ?>
                                                    <div class="dashboard-list-subtitle">开始: <?= $h(date('Y-m-d', strtotime($campaign['start_date']))) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
    </div>

    <!-- 七、自定义报表分类 -->
    <div class="card content-card">
        <div class="card-body dashboard-card-body">
                    <div class="dashboard-section-header border-custom">
                        <div class="dashboard-section-indicator bg-custom"></div>
                        <h2 class="dashboard-section-title">自定义报表</h2>
                    </div>

                    <div class="dashboard-chart-container">
                        <div class="dashboard-chart-wrapper-lg">
                            <canvas id="customReportChart" class="dashboard-chart" data-series="<?= $h(json_encode($stats['report']['series'] ?? [], JSON_UNESCAPED_UNICODE)) ?>" data-metric="<?= $h(json_encode($reportMetric, JSON_UNESCAPED_UNICODE)) ?>"></canvas>
                        </div>
                    </div>
                </div>
    </div>
</div>
