<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>作品管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <h2>作品管理</h2>
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>封面</th>
                <th>标题</th>
                <th>作者</th>
                <th>分类</th>
                <th>状态</th>
                <th>字数</th>
                <th>浏览量</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($books)): ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                        <td><img src="<?php echo htmlspecialchars($book['cover_image'] ?: ''); ?>" alt="" width="50" style="border-radius:4px;"></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td>
                            <?php if ($book['status'] == '连载'): ?>
                                <span class="badge bg-success">连载</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">完结</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($book['word_count']); ?></td>
                        <td><?php echo htmlspecialchars($book['views']); ?></td>
                        <td>
                            <a href="/admin/bookstore/sync_book/<?php echo $book['id']; ?>" class="btn btn-sm btn-info">同步</a>
                            <a href="/admin/bookstore/sync_chapters/<?php echo $book['id']; ?>" class="btn btn-sm btn-warning">章节</a>
                            <a href="#" class="btn btn-sm btn-primary">编辑</a>
                            <a href="#" class="btn btn-sm btn-danger">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">暂无作品。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>