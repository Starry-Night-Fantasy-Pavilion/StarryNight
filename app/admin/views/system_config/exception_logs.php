<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header">
        <h2 class="sysconfig-card-title">异常日志</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>级别</th>
                        <th>消息</th>
                        <th>文件</th>
                        <th>行号</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="text-center text-muted">暂无日志</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $log): ?>
                            <tr>
                                <td><?= (int)$log['id'] ?></td>
                                <td>
                                    <?php
                                    $level = $log['level'] ?? 'info';
                                    $badgeClass = match($level) {
                                        'error', 'critical' => 'bg-danger',
                                        'warning' => 'bg-warning',
                                        'info' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($level) ?></span>
                                </td>
                                <td class="sysconfig-table-cell-w300">
                                    <?= htmlspecialchars(mb_substr($log['message'] ?? '', 0, 100)) ?>
                                    <?= mb_strlen($log['message'] ?? '') > 100 ? '...' : '' ?>
                                </td>
                                <td class="sysconfig-table-cell-w200">
                                    <?= htmlspecialchars(mb_substr($log['file'] ?? '', 0, 50)) ?>
                                    <?= mb_strlen($log['file'] ?? '') > 50 ? '...' : '' ?>
                                </td>
                                <td><?= (int)($log['line'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($hasMore): ?>
        <div class="sysconfig-load-more">
            <a href="?page=<?= $page + 1 ?>" class="btn btn-primary">加载更多</a>
        </div>
        <?php endif; ?>
    </div>
</div>
