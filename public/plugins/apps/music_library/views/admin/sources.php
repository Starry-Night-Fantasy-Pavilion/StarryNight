<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>音乐源管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>音乐源管理</h2>
        <a href="/admin/music/sources/add" class="btn btn-success">+ 添加音乐源</a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>音乐源</th>
                    <th>类型</th>
                    <th>状态</th>
                    <th>最后同步</th>
                    <th>统计</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sources)): ?>
                    <?php foreach ($sources as $source): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($source['name']); ?></div>
                                <div class="text-muted" style="font-size: 12px; word-break: break-all;"><?php echo htmlspecialchars($source['url']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($source['type']); ?></td>
                            <td>
                                <?php 
                                switch ($source['status']) {
                                    case 'active': 
                                        echo '<span class="badge bg-success">活跃</span>'; 
                                        break;
                                    case 'inactive': 
                                        echo '<span class="badge bg-danger">未激活</span>'; 
                                        break;
                                    case 'error': 
                                        echo '<span class="badge bg-warning">错误</span>'; 
                                        break;
                                    default: 
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($source['status']) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $source['last_sync'] ? date('Y-m-d H:i', strtotime($source['last_sync'])) : '从未同步'; ?></td>
                            <td>
                                <div class="text-muted" style="font-size: 13px;">
                                    音乐: <strong><?php echo $source['track_count'] ?? 0; ?></strong> &nbsp;|&nbsp;
                                    专辑: <strong><?php echo $source['album_count'] ?? 0; ?></strong>
                                </div>
                            </td>
                            <td>
                                <a href="/admin/music/sources/edit/<?php echo $source['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                                <button class="btn btn-sm btn-success" onclick="testSource(<?php echo $source['id']; ?>)">测试</button>
                                <button class="btn btn-sm btn-warning" onclick="syncSource(<?php echo $source['id']; ?>)">同步</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteSource(<?php echo $source['id']; ?>)">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">暂无音乐源</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function testSource(id) {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '测试中...';
        button.disabled = true;
        
        fetch('/api/v1/admin/music/sources/' + id + '/test', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            button.textContent = originalText;
            button.disabled = false;
            
            if (data.success) {
                alert('测试成功！连接正常。');
            } else {
                alert('测试失败: ' + data.message);
            }
        })
        .catch(error => {
            button.textContent = originalText;
            button.disabled = false;
            alert('测试失败: ' + error.message);
        });
    }
    
    function syncSource(id) {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '同步中...';
        button.disabled = true;
        
        fetch('/api/v1/admin/music/sources/' + id + '/sync', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            button.textContent = originalText;
            button.disabled = false;
            
            if (data.success) {
                alert('同步成功！已导入 ' + data.data.tracks_added + ' 首音乐和 ' + data.data.albums_added + ' 个专辑。');
                location.reload();
            } else {
                alert('同步失败: ' + data.message);
            }
        })
        .catch(error => {
            button.textContent = originalText;
            button.disabled = false;
            alert('同步失败: ' + error.message);
        });
    }
    
    function deleteSource(id) {
        if (confirm('确定要删除这个音乐源吗？此操作不可撤销。')) {
            fetch('/api/v1/admin/music/sources/' + id, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('删除成功');
                    location.reload();
                } else {
                    alert('删除失败: ' + data.message);
                }
            });
        }
    }
</script>
</body>
</html>
