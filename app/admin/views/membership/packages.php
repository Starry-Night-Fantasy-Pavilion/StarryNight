<?php
// 会员套餐列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>会员套餐</title>
</head>
<body>
    <h1>会员套餐</h1>

    <p><a href="?action=add">新增套餐</a></p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>类型</th>
            <th>时长(天)</th>
            <th>原价</th>
            <th>折扣价</th>
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
                    <td><?= htmlspecialchars((string)($pkg['type'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['duration_days'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['original_price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($pkg['discount_price'] ?? '')) ?></td>
                    <td><?= !empty($pkg['is_enabled']) ? '是' : '否' ?></td>
                    <td>
                        <a href="?action=edit&id=<?= urlencode((string)($pkg['id'] ?? '')) ?>">编辑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">暂无套餐</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

