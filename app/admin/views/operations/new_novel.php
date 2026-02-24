<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
$days = $days ?? 30;
$group = $group ?? 'day';
$data = $data ?? ['total' => 0, 'today' => 0, 'yesterday' => 0, 'growth_rate' => 0, 'chart_data' => []];
?>
<div class="operations-page" data-page-type="new_novel" data-chart-data='<?= json_encode($data['chart_data']) ?>'>
    <div class="operations-card">
        <div class="operations-card-body">
            <div class="dashboard-section-header border-books">
                        <div class="dashboard-section-indicator bg-books"></div>
                        <h2 class="dashboard-section-title">新增小说</h2>
                        <a href="/<?= $adminPrefix ?>" class="dashboard-back-button" title="返回仪表盘">
                            <?= render_icon('arrow-left', ['width' => '16', 'height' => '16']) ?>
                            <span>返回</span>
                        </a>
                    </div>
                    
                    <div class="operations-filters">
                        <form method="GET" class="dashboard-filter-form">
                            <select name="days" class="dashboard-select" onchange="this.form.submit()">
                                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>最近7天</option>
                                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>最近30天</option>
                                <option value="90" <?= $days == 90 ? 'selected' : '' ?>>最近90天</option>
                                <option value="180" <?= $days == 180 ? 'selected' : '' ?>>最近180天</option>
                                <option value="365" <?= $days == 365 ? 'selected' : '' ?>>最近365天</option>
                            </select>
                            <select name="group" class="dashboard-select" onchange="this.form.submit()">
                                <option value="day" <?= $group == 'day' ? 'selected' : '' ?>>按天</option>
                                <option value="week" <?= $group == 'week' ? 'selected' : '' ?>>按周</option>
                                <option value="month" <?= $group == 'month' ? 'selected' : '' ?>>按月</option>
                            </select>
                        </form>
                    </div>

                    <div class="dashboard-grid-4-cols">
                        <div class="dashboard-stat-card" data-type="novel">
                            <div class="dashboard-stat-card-header">
                                <div class="dashboard-stat-icon bg-novel">
                                    <?= render_icon('book', ['width' => '24', 'height' => '24']) ?>
                                </div>
                                <div class="dashboard-stat-trend neutral">总计</div>
                            </div>
                            <div class="dashboard-stat-content">
                                <div class="dashboard-stat-label">总小说数</div>
                                <div class="dashboard-stat-value"><?= number_format($data['total']) ?></div>
                                <div class="dashboard-stat-sub">累计小说数量</div>
                            </div>
                            <div class="dashboard-stat-details">
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['today']) ?></div>
                                    <div class="dashboard-stat-detail-label">今日</div>
                                </div>
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['yesterday']) ?></div>
                                    <div class="dashboard-stat-detail-label">昨日</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dashboard-stat-card" data-type="novel">
                            <div class="dashboard-stat-card-header">
                                <div class="dashboard-stat-icon bg-novel">
                                    <?= render_icon('plus-circle', ['width' => '24', 'height' => '24']) ?>
                                </div>
                                <div class="dashboard-stat-trend 
                                    <?php if ($data['growth_rate'] > 0): ?>
                                        up
                                    <?php elseif ($data['growth_rate'] < 0): ?>
                                        down
                                    <?php else: ?>
                                        neutral
                                    <?php endif; ?>
                                ">
                                    <?php if ($data['growth_rate'] > 0): ?>
                                        ↑ <?= abs($data['growth_rate']) ?>%
                                    <?php elseif ($data['growth_rate'] < 0): ?>
                                        ↓ <?= abs($data['growth_rate']) ?>%
                                    <?php else: ?>
                                        持平
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dashboard-stat-content">
                                <div class="dashboard-stat-label">今日新增</div>
                                <div class="dashboard-stat-value"><?= number_format($data['today']) ?></div>
                                <div class="dashboard-stat-sub">较昨日数据对比</div>
                            </div>
                            <div class="dashboard-stat-details">
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['yesterday']) ?></div>
                                    <div class="dashboard-stat-detail-label">昨日</div>
                                </div>
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value">
                                        <?php if ($data['yesterday'] > 0): ?>
                                            <?= round(($data['today'] / $data['yesterday'] - 1) * 100, 1) ?>%
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </div>
                                    <div class="dashboard-stat-detail-label">变化</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dashboard-stat-card" data-type="novel">
                            <div class="dashboard-stat-card-header">
                                <div class="dashboard-stat-icon bg-novel">
                                    <?= render_icon('calendar', ['width' => '24', 'height' => '24']) ?>
                                </div>
                                <div class="dashboard-stat-trend neutral">基准</div>
                            </div>
                            <div class="dashboard-stat-content">
                                <div class="dashboard-stat-label">昨日新增</div>
                                <div class="dashboard-stat-value"><?= number_format($data['yesterday']) ?></div>
                                <div class="dashboard-stat-sub">对比基准数据</div>
                            </div>
                            <div class="dashboard-stat-details">
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['today']) ?></div>
                                    <div class="dashboard-stat-detail-label">今日</div>
                                </div>
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value">
                                        <?php if ($data['yesterday'] > 0): ?>
                                            <?= round(($data['today'] / $data['yesterday'] - 1) * 100, 1) ?>%
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </div>
                                    <div class="dashboard-stat-detail-label">变化</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dashboard-stat-card" data-type="novel">
                            <div class="dashboard-stat-card-header">
                                <div class="dashboard-stat-icon bg-novel">
                                    <?= render_icon('trending-up', ['width' => '24', 'height' => '24']) ?>
                                </div>
                                <div class="dashboard-stat-trend 
                                    <?php if ($data['growth_rate'] > 0): ?>
                                        up
                                    <?php elseif ($data['growth_rate'] < 0): ?>
                                        down
                                    <?php else: ?>
                                        neutral
                                    <?php endif; ?>
                                ">
                                    <?php if ($data['growth_rate'] > 0): ?>
                                        增长
                                    <?php elseif ($data['growth_rate'] < 0): ?>
                                        下降
                                    <?php else: ?>
                                        持平
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="dashboard-stat-content">
                                <div class="dashboard-stat-label">增长率</div>
                                <div class="dashboard-stat-value">
                                    <?php if ($data['growth_rate'] > 0): ?>
                                        +<?= $data['growth_rate'] ?>%
                                    <?php elseif ($data['growth_rate'] < 0): ?>
                                        <?= $data['growth_rate'] ?>%
                                    <?php else: ?>
                                        0%
                                    <?php endif; ?>
                                </div>
                                <div class="dashboard-stat-sub">日环比增长率</div>
                            </div>
                            <div class="dashboard-stat-details">
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['today']) ?></div>
                                    <div class="dashboard-stat-detail-label">当前</div>
                                </div>
                                <div class="dashboard-stat-detail-item">
                                    <div class="dashboard-stat-detail-value"><?= number_format($data['yesterday']) ?></div>
                                    <div class="dashboard-stat-detail-label">基准</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-chart-container">
                        <div class="dashboard-chart-title">新增小说趋势</div>
                        <div class="dashboard-chart-wrapper-lg">
                            <canvas id="newNovelChart" class="dashboard-chart"></canvas>
                        </div>
                    </div>

                    <!-- 数据表格 -->
                    <?php if (!empty($data['chart_data'])): ?>
                    <div class="operations-data-table">
                        <div class="operations-table-header">
                            <h3>详细数据</h3>
                            <button class="operations-export-btn" data-export-type="newNovel">导出数据</button>
                        </div>
                        <div class="operations-table-container">
                            <table class="operations-table">
                                <thead>
                                    <tr>
                                        <th>日期</th>
                                        <th>新增数量</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $chartData = array_reverse($data['chart_data']);
                                    foreach ($chartData as $item): 
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['label'] ?? '') ?></td>
                                        <td class="text-books"><?= number_format((int)($item['value'] ?? 0)) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
        </div>
    </div>
</div>
