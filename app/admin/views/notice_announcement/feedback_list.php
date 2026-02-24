<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header">
        <h2 style="margin:0;">用户反馈</h2>
        <form method="GET" style="display:flex; gap:8px;">
            <select name="type" class="form-control" style="width:120px;" onchange="this.form.submit()">
                <option value="">全部类型</option>
                <option value="feedback" <?= ($_GET['type'] ?? '') === 'feedback' ? 'selected' : '' ?>>反馈</option>
                <option value="complaint" <?= ($_GET['type'] ?? '') === 'complaint' ? 'selected' : '' ?>>投诉</option>
                <option value="consultation" <?= ($_GET['type'] ?? '') === 'consultation' ? 'selected' : '' ?>>咨询</option>
            </select>
            <select name="status" class="form-control" style="width:120px;" onchange="this.form.submit()">
                <option value="">全部状态</option>
                <option value="open" <?= ($_GET['status'] ?? '') === 'open' ? 'selected' : '' ?>>待处理</option>
                <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>处理中</option>
                <option value="resolved" <?= ($_GET['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>已解决</option>
                <option value="closed" <?= ($_GET['status'] ?? '') === 'closed' ? 'selected' : '' ?>>已关闭</option>
            </select>
            <button type="submit" class="btn btn-primary">筛选</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户</th>
                        <th>类型</th>
                        <th>主题/摘要</th>
                        <th>状态</th>
                        <th>提交时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="7" class="text-center text-muted">暂无反馈</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td><?= (int)$row['id'] ?></td>
                                <td>#<?= (int)$row['user_id'] ?> <?= htmlspecialchars($row['nickname'] ?? $row['username'] ?? '') ?></td>
                                <td><?= $row['type'] === 'feedback' ? '反馈' : ($row['type'] === 'complaint' ? '投诉' : '咨询') ?></td>
                                <td style="max-width:200px;"><?= htmlspecialchars(mb_substr($row['subject'] ?? $row['content'] ?? '', 0, 40)) ?>...</td>
                                <td><?= $row['status'] === 'open' ? '待处理' : ($row['status'] === 'in_progress' ? '处理中' : ($row['status'] === 'resolved' ? '已解决' : '已关闭')) ?></td>
                                <td><?= !empty($row['created_at']) ? date('Y-m-d H:i', strtotime($row['created_at'])) : '' ?></td>
                                <td><a href="/<?= $adminPrefix ?>/feedback/detail/<?= (int)$row['id'] ?>">详情</a> <a href="/<?= $adminPrefix ?>/feedback/delete/<?= (int)$row['id'] ?>" onclick="return confirm('确定删除？');">删除</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($page > 1 || !empty($hasMore)): ?>
            <div class="mt-3" style="display: flex; justify-content: space-between; align-items: center;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => 1])) ?>" class="btn btn-secondary">上一页</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <span>第 <?= $page ?> 页</span>
                <?php if (!empty($hasMore)): ?>
                    <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => 1])) ?>" class="btn btn-secondary">下一页</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
