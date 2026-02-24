<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>书源管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>书源管理</h2>
        <a href="/<?php echo get_env('ADMIN_PATH', 'admin'); ?>/my_app/edit_source" class="btn btn-success">添加新书源</a>
    </div>

    <?php if ($message ?? ''): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>书源名称</th>
                <th>分组</th>
                <th>地址</th>
                <th>类型</th>
                <th>启用</th>
                <th>权重</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sources)): ?>
                <?php foreach ($sources as $source): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($source['id']); ?></td>
                        <td><?php echo htmlspecialchars($source['book_source_name']); ?></td>
                        <td><?php echo htmlspecialchars($source['book_source_group'] ?? 'N/A'); ?></td>
                        <td><a href="<?php echo htmlspecialchars($source['book_source_url']); ?>" target="_blank" title="<?php echo htmlspecialchars($source['book_source_url']); ?>"><?php echo substr(htmlspecialchars($source['book_source_url']), 0, 30) . '...'; ?></a></td>
                        <td><?php echo $source['book_source_type'] == 1 ? '音频' : '文本'; ?></td>
                        <td><?php echo $source['enabled'] ? '<span class="badge bg-success">是</span>' : '<span class="badge bg-danger">否</span>'; ?></td>
                        <td><?php echo htmlspecialchars($source['weight']); ?></td>
                        <td>
                            <a href="/<?php echo get_env('ADMIN_PATH', 'admin'); ?>/my_app/edit_source/<?php echo $source['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                            <a href="/<?php echo get_env('ADMIN_PATH', 'admin'); ?>/my_app/delete_source/<?php echo $source['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除吗？');">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">暂无书源。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>