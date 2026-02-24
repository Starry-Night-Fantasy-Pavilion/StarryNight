<?php
// 知识条目列表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>知识条目列表</title>
</head>
<body>
    <h1>知识条目列表</h1>

    <?php if (!empty($knowledgeBase)): ?>
        <p>所属知识库：<?= htmlspecialchars((string)($knowledgeBase['title'] ?? '')) ?></p>
    <?php endif; ?>

    <p><a href="/admin/knowledge/create-item/<?= htmlspecialchars((string)($knowledgeBase['id'] ?? $knowledgeBaseId ?? '')) ?>">新建条目</a></p>

    <?php $items = $itemsResult['items'] ?? $itemsResult['data'] ?? []; ?>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>标题</th>
            <th>类型</th>
            <th>标签</th>
            <th>排序</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($items) && is_iterable($items)): ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($item['id'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($item['title'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($item['content_type'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($item['tags'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($item['order_index'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">暂无条目</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

