<?php
// 充值套餐列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>充值套餐</title>
</head>
<body>
    <h1>充值套餐</h1>

    <p><a href="?action=add">新增充值套餐</a></p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>代币数</th>
            <th>价格</th>
            <th>VIP 价</th>
            <th>是否热门</th>
            <th>是否启用</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($packages) && is_iterable($packages)): ?>
            <?php foreach ($packages as $pkg): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($pkg['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['tokens'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['vip_price'] ?? '')) ?></td>
                    <td><?= !empty($pkg['is_hot']) ? '是' : '否' ?></td>
                    <td><?= !empty($pkg['is_enabled']) ? '是' : '否' ?></td>
                    <td>
                        <a href="?action=edit&id=<?= urlencode((string)($pkg['id'] ?? '')) ?>">编辑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">暂无充值套餐</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

