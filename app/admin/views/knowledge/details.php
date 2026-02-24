<?php
// 知识库详情
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>知识库详情</title>
</head>
<body>
    <h1>知识库详情</h1>

    <?php if (!empty($knowledgeBase)): ?>
        <h2><?= htmlspecialchars((string)($knowledgeBase['title'] ?? '')) ?></h2>
        <p>描述：<?= nl2br(htmlspecialchars((string)($knowledgeBase['description'] ?? ''))) ?></p>
        <p>分类：<?= htmlspecialchars((string)($knowledgeBase['category'] ?? '')) ?></p>
        <p>可见性：<?= htmlspecialchars((string)($knowledgeBase['visibility'] ?? '')) ?></p>
        <p>价格：<?= htmlspecialchars((string)($knowledgeBase['price'] ?? '')) ?></p>
    <?php endif; ?>

    <h2>条目列表</h2>
    <?php $items = $itemsResult['items'] ?? $itemsResult['data'] ?? []; ?>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>ID</th>
            <th>标题</th>
            <th>类型</th>
            <th>标签</th>
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
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">暂无条目</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h2>统计数据</h2>
    <pre><?php echo htmlspecialchars(json_encode($stats ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>

    <h2>最近评分</h2>
    <pre><?php echo htmlspecialchars(json_encode($ratingsResult ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>

    <h2>最近购买</h2>
    <pre><?php echo htmlspecialchars(json_encode($purchasesResult ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
</body>
</html>

