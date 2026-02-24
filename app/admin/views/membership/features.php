<?php
// 功能权限列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>功能权限管理</title>
</head>
<body>
    <h1>功能权限管理</h1>

    <p><a href="?action=add">新增功能</a></p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>Key</th>
            <th>名称</th>
            <th>分类</th>
            <th>需 VIP</th>
            <th>启用</th>
            <th>排序</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($features) && is_iterable($features)): ?>
            <?php foreach ($features as $feature): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($feature['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($feature['feature_key'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($feature['feature_name'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($feature['category'] ?? '')) ?></td>
                    <td><?= !empty($feature['require_vip']) ? '是' : '否' ?></td>
                    <td><?= !empty($feature['is_enabled']) ? '是' : '否' ?></td>
                    <td><?= htmlspecialchars((string)($feature['sort_order'] ?? 0)) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= urlencode((string)($feature['id'] ?? '')) ?>">编辑</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">暂无功能</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

