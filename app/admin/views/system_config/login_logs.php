<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="card">
    <div class="card-header">
        <h2 class="sysconfig-card-title">登录日志</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>IP地址</th>
                        <th>User Agent</th>
                        <th>结果</th>
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
                                <td><?= htmlspecialchars($log['username'] ?? '') ?></td>
                                <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                                <td class="sysconfig-table-cell-w300">
                                    <?= htmlspecialchars(mb_substr($log['user_agent'] ?? '', 0, 80)) ?>
                                    <?= mb_strlen($log['user_agent'] ?? '') > 80 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($log['result'] ?? '') === 'success' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= htmlspecialchars($log['result'] ?? '') ?>
                                    </span>
                                </td>
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
