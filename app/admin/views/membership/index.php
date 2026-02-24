<?php
// 会员管理总览
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>会员总览</title>
</head>
<body>
    <h1>会员总览</h1>
    <p>总用户数：<?= htmlspecialchars((string)($totalUsers ?? 0)) ?></p>
    <p>VIP 用户数：<?= htmlspecialchars((string)($vipUsers ?? 0)) ?></p>
    <p>累计收入：<?= htmlspecialchars((string)($totalRevenue ?? 0)) ?></p>
    <p>本月收入：<?= htmlspecialchars((string)($monthlyRevenue ?? 0)) ?></p>

    <h2>最近会员购买记录</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>用户</th>
            <th>套餐</th>
            <th>金额</th>
            <th>时间</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($recentPurchases) && is_iterable($recentPurchases)): ?>
            <?php foreach ($recentPurchases as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($row['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['user_id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['package_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['actual_price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['payment_time'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">暂无数据</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2>最近充值记录</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>用户</th>
            <th>金额</th>
            <th>时间</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($recentRecharges) && is_iterable($recentRecharges)): ?>
            <?php foreach ($recentRecharges as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($row['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['user_id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['actual_price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($row['payment_time'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">暂无数据</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

