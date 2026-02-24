<?php
// 知识库统计
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>知识库统计</title>
</head>
<body>
    <h1>知识库统计</h1>

    <p>知识库总数：<?= htmlspecialchars((string)($totalKnowledgeBases ?? 0)) ?></p>
    <p>条目总数：<?= htmlspecialchars((string)($totalItems ?? 0)) ?></p>
    <p>购买总数：<?= htmlspecialchars((string)($totalPurchases ?? 0)) ?></p>
    <p>评分总数：<?= htmlspecialchars((string)($totalRatings ?? 0)) ?></p>
</body>
</html>

