<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header">
        <h2 class="sysconfig-card-title">操作日志</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>管理员ID</th>
                        <th>模块</th>
                        <th>操作</th>
                        <th>数据</th>
                        <th>结果</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="7" class="text-center text-muted">暂无日志</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $log): ?>
                            <tr>
                                <td><?= (int)$log['id'] ?></td>
                                <td><?= $log['admin_id'] ? (int)$log['admin_id'] : '-' ?></td>
                                <td><?= htmlspecialchars($log['module'] ?? '') ?></td>
                                <td><?= htmlspecialchars($log['action'] ?? '') ?></td>
                                <td class="sysconfig-table-cell-w300">
                                    <?= htmlspecialchars(mb_substr($log['data'] ?? '', 0, 100)) ?>
                                    <?= mb_strlen($log['data'] ?? '') > 100 ? '...' : '' ?>
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
