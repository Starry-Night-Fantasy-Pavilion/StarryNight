<?php
// 会员权益列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>会员权益管理</title>
</head>
<body>
    <h1>会员权益管理</h1>

    <p><a href="?action=add">新增权益</a></p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>Key</th>
            <th>名称</th>
            <th>类型</th>
            <th>值</th>
            <th>启用</th>
            <th>排序</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($benefits) && is_iterable($benefits)): ?>
            <?php foreach ($benefits as $benefit): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($benefit['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($benefit['benefit_key'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($benefit['benefit_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($benefit['benefit_type'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($benefit['value'] ?? '')) ?></td>
                    <td><?= !empty($benefit['is_enabled']) ? '是' : '否' ?></td>
                    <td><?= htmlspecialchars((string)($benefit['sort_order'] ?? 0)) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= urlencode((string)($benefit['id'] ?? '')) ?>">编辑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">暂无权益</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

