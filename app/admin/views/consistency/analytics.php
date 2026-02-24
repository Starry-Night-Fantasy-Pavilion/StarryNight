<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '');

// 视图内工具函数，避免使用 $this 导致 500
if (!function_exists('consistency_get_check_type_label')) {
    function consistency_get_check_type_label(string $type): string
    {
        $labels = [
            'full'      => '全面检�?,
            'worldview' => '世界观检�?,
            'character' => '角色检�?,
            'event'     => '事件检�?,
            'rule'      => '规则检�?,
        ];
        return $labels[$type] ?? $type;
    }
}
?>

<link rel="stylesheet" href="/static/frontend/views/css/consistency-analytics.css?v=<?= time() ?>">

<div class="consistency-check-container" data-admin-prefix="<?= htmlspecialchars(trim((string)$adminPrefix, '/'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="page-header">
        <h1 class="page-title">分析统计</h1>
        <p class="page-description">查看一致性检查系统的使用统计和性能分析</p>
    </div>

    <div class="consistency-nav-tabs">
        <ul class="nav-tabs">
            <li class="nav-tab <?= ($currentPage === 'consistency-config') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/config">系统配置</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-core-settings') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/core-settings">核心设定</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-check') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/check">一致性检�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-reports') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/reports">检查报�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-analytics') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/analytics">分析统计</a>
            </li>
        </ul>
    </div>

    <div class="analytics-content">
        <div class="time-range-selector">
            <div class="range-buttons">
                <button class="btn btn-outline range-btn <?= ($timeRange ?? 'week') === 'today' ? 'active' : '' ?>" 
                        data-range="today">今天</button>
                <button class="btn btn-outline range-btn <?= ($timeRange ?? 'week') === 'week' ? 'active' : '' ?>" 
                        data-range="week">最�?�?/button>
                <button class="btn btn-outline range-btn <?= ($timeRange ?? 'week') === 'month' ? 'active' : '' ?>" 
                        data-range="month">最�?0�?/button>
                <button class="btn btn-outline range-btn <?= ($timeRange ?? 'week') === 'quarter' ? 'active' : '' ?>" 
                        data-range="quarter">最�?个月</button>
                <button class="btn btn-outline range-btn <?= ($timeRange ?? 'week') === 'year' ? 'active' : '' ?>" 
                        data-range="year">最�?�?/button>
            </div>
            <div class="date-range">
                <input type="date" id="startDate" class="form-input" value="<?= $startDate ?? '' ?>">
                <span>�?/span>
                <input type="date" id="endDate" class="form-input" value="<?= $endDate ?? '' ?>">
                <button class="btn btn-primary" type="button" id="btnApplyDateRange">应用</button>
            </div>
        </div>

        <div class="overview-cards">
            <div class="overview-card">
                <div class="card-icon">
                    <i class="icon">📊</i>
                </div>
                <div class="card-content">
                    <h3>总检查次�?/h3>
                    <p class="card-value"><?= $analytics['total_checks'] ?? 0 ?></p>
                    <div class="card-trend trend-<?= $analytics['checks_trend'] ?? 'neutral' ?>">
                        <i class="icon"><?= $analytics['checks_trend'] === 'up' ? '📈' : ($analytics['checks_trend'] === 'down' ? '📉' : '➡️') ?></i>
                        <span><?= $analytics['checks_change'] ?? '0%' ?> vs 上期</span>
                    </div>
                </div>
            </div>

            <div class="overview-card">
                <div class="card-icon">
                    <i class="icon">⚠️</i>
                </div>
                <div class="card-content">
                    <h3>发现冲突</h3>
                    <p class="card-value"><?= $analytics['total_conflicts'] ?? 0 ?></p>
                    <div class="card-trend trend-<?= $analytics['conflicts_trend'] ?? 'neutral' ?>">
                        <i class="icon"><?= $analytics['conflicts_trend'] === 'up' ? '📈' : ($analytics['conflicts_trend'] === 'down' ? '📉' : '➡️') ?></i>
                        <span><?= $analytics['conflicts_change'] ?? '0%' ?> vs 上期</span>
                    </div>
                </div>
            </div>

            <div class="overview-card">
                <div class="card-icon">
                    <i class="icon">�?/i>
                </div>
                <div class="card-content">
                    <h3>通过�?/h3>
                    <p class="card-value"><?= ($analytics['pass_rate'] ?? 0) ?>%</p>
                    <div class="card-trend trend-<?= $analytics['pass_rate_trend'] ?? 'neutral' ?>">
                        <i class="icon"><?= $analytics['pass_rate_trend'] === 'up' ? '📈' : ($analytics['pass_rate_trend'] === 'down' ? '📉' : '➡️') ?></i>
                        <span><?= $analytics['pass_rate_change'] ?? '0%' ?> vs 上期</span>
                    </div>
                </div>
            </div>

            <div class="overview-card">
                <div class="card-icon">
                    <i class="icon">⏱️</i>
                </div>
                <div class="card-content">
                    <h3>平均检查时�?/h3>
                    <p class="card-value"><?= $analytics['avg_check_time'] ?? 0 ?>s</p>
                    <div class="card-trend trend-<?= $analytics['check_time_trend'] ?? 'neutral' ?>">
                        <i class="icon"><?= $analytics['check_time_trend'] === 'up' ? '📈' : ($analytics['check_time_trend'] === 'down' ? '📉' : '➡️') ?></i>
                        <span><?= $analytics['check_time_change'] ?? '0%' ?> vs 上期</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-section">
            <div class="chart-row">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>检查趋�?/h3>
                        <div class="chart-controls">
                            <select id="trendMetric" class="form-select">
                                <option value="checks">检查次�?/option>
                                <option value="conflicts">冲突数量</option>
                                <option value="pass_rate">通过�?/option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-canvas">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3>冲突类型分布</h3>
                    </div>
                    <div class="chart-canvas">
                        <canvas id="conflictTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-row">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>严重程度分布</h3>
                    </div>
                    <div class="chart-canvas">
                        <canvas id="severityChart"></canvas>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3>检查类型统�?/h3>
                    </div>
                    <div class="chart-canvas">
                        <canvas id="checkTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="performance-section">
            <div class="section-header">
                <h2>系统性能</h2>
                <p>向量数据库和嵌入模型的性能指标</p>
            </div>

            <div class="performance-grid">
                <div class="performance-card">
                    <div class="perf-header">
                        <h4>向量数据�?/h4>
                        <div class="perf-status status-<?= $performance['vector_db']['status'] ?? 'unknown' ?>">
                            <?= $performance['vector_db']['status_text'] ?? '未知' ?>
                        </div>
                    </div>
                    <div class="perf-metrics">
                        <div class="metric-item">
                            <label>连接状�?</label>
                            <span><?= $performance['vector_db']['connected'] ? '已连�? : '未连�? ?></span>
                        </div>
                        <div class="metric-item">
                            <label>响应时间:</label>
                            <span><?= $performance['vector_db']['response_time'] ?? 0 ?>ms</span>
                        </div>
                        <div class="metric-item">
                            <label>存储使用:</label>
                            <span><?= $performance['vector_db']['storage_used'] ?? 0 ?>MB</span>
                        </div>
                        <div class="metric-item">
                            <label>索引数量:</label>
                            <span><?= $performance['vector_db']['index_count'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>

                <div class="performance-card">
                    <div class="perf-header">
                        <h4>嵌入模型</h4>
                        <div class="perf-status status-<?= $performance['embedding_model']['status'] ?? 'unknown' ?>">
                            <?= $performance['embedding_model']['status_text'] ?? '未知' ?>
                        </div>
                    </div>
                    <div class="perf-metrics">
                        <div class="metric-item">
                            <label>模型状�?</label>
                            <span><?= $performance['embedding_model']['available'] ? '可用' : '不可�? ?></span>
                        </div>
                        <div class="metric-item">
                            <label>处理时间:</label>
                            <span><?= $performance['embedding_model']['process_time'] ?? 0 ?>ms</span>
                        </div>
                        <div class="metric-item">
                            <label>今日调用:</label>
                            <span><?= $performance['embedding_model']['daily_calls'] ?? 0 ?></span>
                        </div>
                        <div class="metric-item">
                            <label>成功�?</label>
                            <span><?= ($performance['embedding_model']['success_rate'] ?? 0) ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="performance-card">
                    <div class="perf-header">
                        <h4>系统资源</h4>
                        <div class="perf-status status-<?= $performance['system']['status'] ?? 'unknown' ?>">
                            <?= $performance['system']['status_text'] ?? '未知' ?>
                        </div>
                    </div>
                    <div class="perf-metrics">
                        <div class="metric-item">
                            <label>CPU使用:</label>
                            <span><?= $performance['system']['cpu_usage'] ?? 0 ?>%</span>
                        </div>
                        <div class="metric-item">
                            <label>内存使用:</label>
                            <span><?= $performance['system']['memory_usage'] ?? 0 ?>%</span>
                        </div>
                        <div class="metric-item">
                            <label>磁盘使用:</label>
                            <span><?= $performance['system']['disk_usage'] ?? 0 ?>%</span>
                        </div>
                        <div class="metric-item">
                            <label>网络延迟:</label>
                            <span><?= $performance['system']['network_latency'] ?? 0 ?>ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="usage-section">
            <div class="section-header">
                <h2>使用统计</h2>
                <p>用户使用情况和热门功能统�?/p>
            </div>

            <div class="usage-grid">
                <div class="usage-card">
                    <h3>最活跃用户</h3>
                    <div class="user-list">
                        <?php foreach ($topUsers as $user): ?>
                        <div class="user-item">
                            <div class="user-avatar">
                                <i class="icon">👤</i>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="user-stats"><?= $user['check_count'] ?> 次检�?/div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="usage-card">
                    <h3>热门检查类�?/h3>
                    <div class="type-list">
                        <?php foreach ($popularTypes as $type): ?>
                        <div class="type-item">
                            <div class="type-name"><?= consistency_get_check_type_label($type['type']) ?></div>
                            <div class="type-bar">
                                <div class="type-fill" data-percent="<?= (float)$type['percentage'] ?>"></div>
                            </div>
                            <div class="type-count"><?= $type['count'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="usage-card">
                    <h3>时间分布</h3>
                    <div class="time-distribution">
                        <?php foreach ($timeDistribution as $period): ?>
                        <div class="time-item">
                            <div class="time-label"><?= $period['label'] ?></div>
                            <div class="time-bar">
                                <div class="time-fill" data-percent="<?= (float)$period['percentage'] ?>"></div>
                            </div>
                            <div class="time-count"><?= $period['count'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
window.CONSISTENCY_ANALYTICS_TREND_LABELS = <?= json_encode($analytics['trend_labels'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_TREND_DATA = <?= json_encode($analytics['trend_data'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_CONFLICT_TYPE_LABELS = <?= json_encode($analytics['conflict_type_labels'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_CONFLICT_TYPE_DATA = <?= json_encode($analytics['conflict_type_data'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_SEVERITY_LABELS = <?= json_encode($analytics['severity_labels'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_SEVERITY_DATA = <?= json_encode($analytics['severity_data'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_CHECK_TYPE_LABELS = <?= json_encode($analytics['check_type_labels'] ?? []) ?>;
window.CONSISTENCY_ANALYTICS_CHECK_TYPE_DATA = <?= json_encode($analytics['check_type_data'] ?? []) ?>;
</script>
<script src="/static/frontend/views/js/consistency-analytics.js?v=<?= time() ?>"></script>
