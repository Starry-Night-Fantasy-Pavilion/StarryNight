<?php
// 知识库列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>知识库列表</title>
</head>
<body>
    <h1>知识库列表</h1>

    <form method="get">
        <label>搜索：
            <input type="text" name="search" value="<?= htmlspecialchars((string)($searchTerm ?? '')) ?>">
        </label>
        <label>分类：
            <input type="text" name="category" value="<?= htmlspecialchars((string)($category ?? '')) ?>">
        </label>
        <label>可见性：
            <input type="text" name="visibility" value="<?= htmlspecialchars((string)($visibility ?? '')) ?>">
        </label>
        <button type="submit">筛选</button>
    </form>

    <p><a href="/admin/knowledge/create">新建知识库</a></p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>标题</th>
            <th>分类</th>
            <th>可见性</th>
            <th>价格</th>
            <th>状态</th>
            <th>创建时间</th>
        </tr>
        </thead>
        <tbody>
        <?php $list = $result['items'] ?? $result['data'] ?? []; ?>
        <?php if (!empty($list) && is_iterable($list)): ?>
            <?php foreach ($list as $kb): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($kb['id'] ?? '')) ?></td>
                    <td><a href="/admin/knowledge/details/<?= urlencode((string)($kb['id'] ?? '')) ?>"><?= htmlspecialchars((string)($kb['title'] ?? '')) ?></a></td>
                    <td><?= htmlspecialchars((string)($kb['category'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($kb['visibility'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($kb['price'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($kb['status'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($kb['created_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">暂无知识库</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

