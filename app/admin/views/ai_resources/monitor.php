<?php
/** @var array $channels */
/** @var array $stats */
/** @var array $recentErrors */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">渠道监控（近 <?= (int)$hours ?> 小时）</div>
    </div>
    <div class="dashboard-card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>渠道</label>
                    <select class="form-control" name="channel_id">
                        <option value="0">全部</option>
                        <?php foreach ($channels as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ((int)$channelId === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name'] ?? '') ?> (<?= htmlspecialchars($c['type'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>小时数</label>
                    <input class="form-control" type="number" name="hours" value="<?= (int)$hours ?>" min="1" max="168">
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit">刷新</button>
                </div>
            </div>
        </form>

        <div class="stats-grid" style="margin-top: 10px;">
            <div class="stat-card">
                <div class="stat-title">调用量</div>
                <div class="stat-value"><?= (int)($stats['total_calls'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">成功率</div>
                <div class="stat-value"><?= htmlspecialchars((string)($stats['success_rate'] ?? 0)) ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">平均延迟</div>
                <div class="stat-value"><?= htmlspecialchars((string)($stats['avg_latency_ms'] ?? 0)) ?> ms</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">最大延迟</div>
                <div class="stat-value"><?= (int)($stats['max_latency_ms'] ?? 0) ?> ms</div>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-card" style="margin-top:16px;">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">最近错误</div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>时间</th>
                    <th>渠道</th>
                    <th>模型</th>
                    <th>HTTP</th>
                    <th>延迟(ms)</th>
                    <th>错误</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentErrors as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['created_at'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['channel_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($e['model_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars((string)($e['http_status'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($e['latency_ms'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($e['error_message'] ?? ($e['error_code'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentErrors)): ?>
                    <tr><td colspan="6">暂无错误日志（或尚未写入调用日志）</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

