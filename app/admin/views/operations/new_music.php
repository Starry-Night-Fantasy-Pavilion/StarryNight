<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
$days = $days ?? 30;
$group = $group ?? 'day';
$data = $data ?? ['total' => 0, 'today' => 0, 'yesterday' => 0, 'growth_rate' => 0, 'chart_data' => []];
?>
<div class="operations-page" data-page-type="new_music" data-chart-data='<?= json_encode($data['chart_data']) ?>'>
    <div class="operations-card">
        <div class="operations-card-body">
            <div class="dashboard-section-header border-music">
                        <div class="dashboard-section-indicator bg-music"></div>
                        <h2 class="dashboard-section-title">新增音乐</h2>
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
                        <div class="dashboard-stat-card border-music">
                            <div class="dashboard-stat-label">总音乐数</div>
                            <div class="dashboard-stat-value text-music"><?= number_format($data['total']) ?></div>
                            <div class="dashboard-stat-sub">累计音乐数量</div>
                        </div>
                        <div class="dashboard-stat-card border-music">
                            <div class="dashboard-stat-label">今日新增</div>
                            <div class="dashboard-stat-value text-music"><?= number_format($data['today']) ?></div>
                            <div class="dashboard-stat-sub">较昨日 
                                <?php if ($data['growth_rate'] > 0): ?>
                                    <span style="color: #32cd32;">↑ <?= abs($data['growth_rate']) ?>%</span>
                                <?php elseif ($data['growth_rate'] < 0): ?>
                                    <span style="color: #ff6384;">↓ <?= abs($data['growth_rate']) ?>%</span>
                                <?php else: ?>
                                    <span>持平</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dashboard-stat-card border-music">
                            <div class="dashboard-stat-label">昨日新增</div>
                            <div class="dashboard-stat-value text-music"><?= number_format($data['yesterday']) ?></div>
                            <div class="dashboard-stat-sub">对比基准</div>
                        </div>
                        <div class="dashboard-stat-card border-music">
                            <div class="dashboard-stat-label">增长率</div>
                            <div class="dashboard-stat-value text-music">
                                <?php if ($data['growth_rate'] > 0): ?>
                                    <span style="color: #32cd32;">+<?= $data['growth_rate'] ?>%</span>
                                <?php elseif ($data['growth_rate'] < 0): ?>
                                    <span style="color: #ff6384;"><?= $data['growth_rate'] ?>%</span>
                                <?php else: ?>
                                    <span>0%</span>
                                <?php endif; ?>
                            </div>
                            <div class="dashboard-stat-sub">日环比</div>
                        </div>
                    </div>

                    <div class="dashboard-chart-container">
                        <div class="dashboard-chart-title">新增音乐趋势</div>
                        <div class="dashboard-chart-wrapper-lg">
                            <canvas id="newMusicChart" class="dashboard-chart"></canvas>
                        </div>
                    </div>

                    <!-- 数据表格 -->
                    <?php if (!empty($data['chart_data'])): ?>
                    <div class="operations-data-table">
                        <div class="operations-table-header">
                            <h3>详细数据</h3>
                            <button class="operations-export-btn" data-export-type="newMusic">导出数据</button>
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
                                        <td class="text-music"><?= number_format((int)($item['value'] ?? 0)) ?></td>
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
