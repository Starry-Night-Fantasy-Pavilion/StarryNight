<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评论管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <h2>评论管理</h2>
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户</th>
                <th>评论内容</th>
                <th>所属作品</th>
                <th>状态</th>
                <th>评论时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comment['id']); ?></td>
                        <td><?php echo htmlspecialchars($comment['username']); ?></td>
                        <td title="<?php echo htmlspecialchars($comment['content']); ?>"><?php echo mb_substr(htmlspecialchars($comment['content']), 0, 30) . '...'; ?></td>
                        <td><?php echo htmlspecialchars($comment['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($comment['status']); ?></td>
                        <td><?php echo htmlspecialchars($comment['created_at']); ?></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-success">批准</a>
                            <a href="#" class="btn btn-sm btn-warning">驳回</a>
                            <a href="#" class="btn btn-sm btn-danger">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">暂无评论。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>