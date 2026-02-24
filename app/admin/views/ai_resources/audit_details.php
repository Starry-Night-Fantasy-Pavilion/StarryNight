<?php
/** @var array $item */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">审核详情 #<?= (int)$item['id'] ?></div>
    </div>
    <div class="dashboard-card-body">
        <div class="table-responsive">
            <table class="table">
                <tbody>
                <tr><th>资源类型</th><td><?= htmlspecialchars($item['resource_type'] ?? '') ?></td></tr>
                <tr><th>资源ID</th><td><?= htmlspecialchars($item['resource_id'] ?? '') ?></td></tr>
                <tr><th>提交人</th><td><?= htmlspecialchars($item['submitter_name'] ?? '-') ?></td></tr>
                <tr><th>期望公开</th><td><?= ((int)($item['desired_public'] ?? 0) === 1) ? '是' : '否' ?></td></tr>
                <tr><th>期望定价</th><td><?= htmlspecialchars((string)($item['desired_price_coin'] ?? '0')) ?></td></tr>
                <tr><th>状态</th><td><?= htmlspecialchars($item['status'] ?? '') ?></td></tr>
                <tr><th>元数据</th><td><pre style="white-space:pre-wrap; margin:0;"><?= htmlspecialchars($item['metadata_json'] ?? '') ?></pre></td></tr>
                </tbody>
            </table>
        </div>

        <form method="POST" action="" style="margin-top: 10px;">
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>备注</label>
                    <input class="form-control" name="comment" placeholder="可选">
                </div>
                <div class="form-group" style="display:flex; gap:10px; align-items:flex-end;">
                    <button class="btn btn-primary" name="action" value="approve" type="submit">通过</button>
                    <button class="btn btn-danger" name="action" value="reject" type="submit">拒绝</button>
                    <button class="btn btn-secondary" name="action" value="comment" type="submit">仅记录</button>
                </div>
            </div>
        </form>

        <div style="margin-top: 16px;">
            <h3 style="margin: 0 0 10px;">日志</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>时间</th>
                        <th>审核人</th>
                        <th>动作</th>
                        <th>备注</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($item['logs'] ?? []) as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['reviewer_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['action'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['comment'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($item['logs'])): ?>
                        <tr><td colspan="4">暂无日志</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

