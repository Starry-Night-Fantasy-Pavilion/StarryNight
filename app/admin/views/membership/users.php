<?php
// 会员用户列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>会员用户列表</title>
</head>
<body>
    <h1>会员用户列表</h1>

    <form method="get">
        <label>
            搜索：
            <input type="text" name="search" value="<?= htmlspecialchars((string)($search ?? '')) ?>">
        </label>
        <label>
            VIP 类型：
            <input type="text" name="vip_type" value="<?= htmlspecialchars((string)($vipType ?? '')) ?>">
        </label>
        <button type="submit">筛选</button>
    </form>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>用户名</th>
            <th>邮箱</th>
            <th>VIP 类型</th>
            <th>状态</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($users) && is_iterable($users)): ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($user['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($user['username'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($user['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($user['vip_type'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($user['status'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">暂无用户</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

