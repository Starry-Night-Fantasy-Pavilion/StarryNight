<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 style="margin:0;">反馈详情 #<?= (int)($feedback['id'] ?? 0) ?></h2></div>
    <div class="card-body">
        <p><strong>用户</strong> #<?= (int)($feedback['user_id'] ?? 0) ?> <?= htmlspecialchars($feedback['nickname'] ?? $feedback['username'] ?? '') ?> (<?= htmlspecialchars($feedback['email'] ?? '') ?>)</p>
        <p><strong>类型</strong> <?= $feedback['type'] === 'feedback' ? '反馈' : ($feedback['type'] === 'complaint' ? '投诉' : '咨询') ?></p>
        <p><strong>状态</strong> <?= $feedback['status'] === 'open' ? '待处理' : ($feedback['status'] === 'in_progress' ? '处理中' : ($feedback['status'] === 'resolved' ? '已解决' : '已关闭')) ?></p>
        <p><strong>主题</strong> <?= htmlspecialchars($feedback['subject'] ?? '') ?></p>
        <p><strong>内容</strong></p>
        <div class="border rounded p-3 mb-3" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($feedback['content'] ?? '')) ?></div>
        <?php if (!empty($attachments)): ?>
            <p><strong>附件</strong></p>
            <ul>
                <?php foreach ($attachments as $a): ?>
                    <li><a href="<?= htmlspecialchars($a['file_path'] ?? '') ?>" target="_blank"><?= htmlspecialchars($a['file_name'] ?? basename($a['file_path'] ?? '')) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($feedback['admin_reply'])): ?>
            <p><strong>管理员回复</strong> (<?= !empty($feedback['replied_at']) ? date('Y-m-d H:i', strtotime($feedback['replied_at'])) : '' ?>)</p>
            <div class="border rounded p-3 mb-3" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($feedback['admin_reply'])) ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-3">
            <input type="hidden" name="_action" value="reply">
            <label class="form-label">管理员回复</label>
            <textarea name="admin_reply" class="form-control" rows="3" placeholder="填写回复内容"><?= htmlspecialchars($feedback['admin_reply'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary mt-2">提交回复</button>
        </form>
        <form method="POST">
            <input type="hidden" name="_action" value="status">
            <label class="form-label">更新状态</label>
            <select name="status" class="form-control" style="width:150px; display:inline-block;">
                <option value="open" <?= ($feedback['status'] ?? '') === 'open' ? 'selected' : '' ?>>待处理</option>
                <option value="in_progress" <?= ($feedback['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>处理中</option>
                <option value="resolved" <?= ($feedback['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>已解决</option>
                <option value="closed" <?= ($feedback['status'] ?? '') === 'closed' ? 'selected' : '' ?>>已关闭</option>
            </select>
            <button type="submit" class="btn btn-secondary">更新状态</button>
        </form>
        <p class="mt-3"><a href="/<?= $adminPrefix ?>/feedback/list">返回列表</a></p>
    </div>
</div>
