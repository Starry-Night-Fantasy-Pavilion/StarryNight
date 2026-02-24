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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>评论管理</h2>
    </div>
    
    <div class="form-section mb-3">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" placeholder="评论内容、用户名或音乐标题" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select class="form-control" id="status">
                        <option value="">全部</option>
                        <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>已通过</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待审核</option>
                        <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="applyFilters()">筛选</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>评论内容</th>
                    <th>用户</th>
                    <th>音乐</th>
                    <th>点赞数</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($comment['content']); ?>">
                                    <?php echo htmlspecialchars($comment['content']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($comment['username'] ?? '匿名用户'); ?></td>
                            <td>
                                <a href="/music/track/<?php echo $comment['track_id']; ?>" target="_blank" style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($comment['track_title']); ?>
                                </a>
                            </td>
                            <td><?php echo $comment['likes']; ?></td>
                            <td>
                                <?php 
                                switch ($comment['status']) {
                                    case 'approved': 
                                        echo '<span class="badge bg-success">已通过</span>'; 
                                        break;
                                    case 'pending': 
                                        echo '<span class="badge bg-warning">待审核</span>'; 
                                        break;
                                    case 'rejected': 
                                        echo '<span class="badge bg-danger">已拒绝</span>'; 
                                        break;
                                    default: 
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($comment['status']) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <?php if ($comment['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="approveComment(<?php echo $comment['id']; ?>)">通过</button>
                                    <button class="btn btn-sm btn-warning" onclick="rejectComment(<?php echo $comment['id']; ?>)">拒绝</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteComment(<?php echo $comment['id']; ?>)">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">暂无评论</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center" style="gap: 10px; margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" 
                   class="btn btn-sm <?php echo $page == $i ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function applyFilters() {
        const search = document.getElementById('search').value;
        const status = document.getElementById('status').value;
        
        let url = '?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (status) url += 'status=' + encodeURIComponent(status) + '&';
        
        window.location.href = url.slice(0, -1);
    }

    function approveComment(id) {
        fetch('/api/v1/admin/music/comments/' + id + '/approve', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('评论已通过');
                location.reload();
            } else {
                alert('操作失败: ' + data.message);
            }
        });
    }
    
    function rejectComment(id) {
        if (confirm('确定要拒绝这条评论吗？')) {
            fetch('/api/v1/admin/music/comments/' + id + '/reject', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('评论已拒绝');
                    location.reload();
                } else {
                    alert('操作失败: ' + data.message);
                }
            });
        }
    }
    
    function deleteComment(id) {
        if (confirm('确定要删除这条评论吗？此操作不可撤销。')) {
            fetch('/api/v1/admin/music/comments/' + id, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('评论已删除');
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
