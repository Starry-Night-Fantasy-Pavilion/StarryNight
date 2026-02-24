<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>
<div class="card">
    <div class="card-header"><h2 style="margin:0;">充值退款（人工审核）</h2></div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <p>订单号：<strong><?= htmlspecialchars($order['order_id'] ?? '') ?></strong> | 用户：<?= htmlspecialchars($order['username'] ?? '') ?> | 金额：¥<?= number_format((float)($order['amount'] ?? 0), 2) ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">退款原因（必填）</label>
                <textarea name="refund_reason" class="form-control" rows="3" required placeholder="请填写退款原因，将记录操作人"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">确认退款</button>
            <a href="/<?= $adminPrefix ?>/finance/orders" class="btn btn-secondary">取消</a>
        </form>
    </div>
</div>
