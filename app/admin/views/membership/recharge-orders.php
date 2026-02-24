<?php
// 充值订单列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>充值订单</title>
</head>
<body>
    <h1>充值订单</h1>

    <form method="get">
        <input type="hidden" name="type" value="recharge">
        <label>
            状态：
            <input type="text" name="status" value="<?= htmlspecialchars((string)($status ?? '')) ?>">
        </label>
        <label>
            搜索：
            <input type="text" name="search" value="<?= htmlspecialchars((string)($search ?? '')) ?>">
        </label>
        <button type="submit">筛选</button>
    </form>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>用户</th>
            <th>套餐</th>
            <th>金额</th>
            <th>状态</th>
            <th>支付时间</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($orders) && is_iterable($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($order['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($order['user_id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($order['package_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($order['actual_price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($order['payment_status'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($order['payment_time'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">暂无订单</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

