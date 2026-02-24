<?php
/** @var array $data */
?>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">资源审核队列</div>
    </div>
    <div class="dashboard-card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>状态</label>
                    <select class="form-control" name="status">
                        <option value="">全部</option>
                        <option value="pending" <?= (($_GET['status'] ?? '') === 'pending') ? 'selected' : '' ?>>待审核</option>
                        <option value="approved" <?= (($_GET['status'] ?? '') === 'approved') ? 'selected' : '' ?>>已通过</option>
                        <option value="rejected" <?= (($_GET['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>已拒绝</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>类型</label>
                    <select class="form-control" name="resource_type">
                        <option value="">全部</option>
                        <option value="knowledge_base" <?= (($_GET['resource_type'] ?? '') === 'knowledge_base') ? 'selected' : '' ?>>知识库</option>
                        <option value="prompt" <?= (($_GET['resource_type'] ?? '') === 'prompt') ? 'selected' : '' ?>>提示词</option>
                        <option value="template" <?= (($_GET['resource_type'] ?? '') === 'template') ? 'selected' : '' ?>>模板</option>
                        <option value="agent" <?= (($_GET['resource_type'] ?? '') === 'agent') ? 'selected' : '' ?>>智能体</option>
                        <option value="other" <?= (($_GET['resource_type'] ?? '') === 'other') ? 'selected' : '' ?>>其他</option>
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button class="btn btn-primary" type="submit">筛选</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table" style="margin-top: 10px;">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>类型</th>
                    <th>资源ID</th>
                    <th>提交人</th>
                    <th>期望公开</th>
                    <th>期望定价</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($data['items'] ?? []) as $it): ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['resource_type'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['resource_id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['submitter_name'] ?? '-') ?></td>
                        <td><?= ((int)($it['desired_public'] ?? 0) === 1) ? '是' : '否' ?></td>
                        <td><?= htmlspecialchars((string)($it['desired_price_coin'] ?? '0')) ?></td>
                        <td><?= htmlspecialchars($it['status'] ?? '') ?></td>
                        <td><?= htmlspecialchars($it['created_at'] ?? '') ?></td>
                        <td>
                            <?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
                            <a class="btn btn-secondary" href="/<?= $adminPrefix ?>/ai/audits/details/<?= (int)$it['id'] ?>">查看</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($data['items'])): ?>
                    <tr><td colspan="9">暂无审核任务</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

