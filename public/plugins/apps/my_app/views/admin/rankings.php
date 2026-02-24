<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>排行榜管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <h2>排行榜管理</h2>
    
    <h4>按浏览量排行</h4>
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>排名</th>
                <th>ID</th>
                <th>标题</th>
                <th>作者</th>
                <th>浏览量</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rankings)): ?>
                <?php foreach ($rankings as $index => $book): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?php echo $index + 1; ?></span></td>
                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['views']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">暂无数据。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>