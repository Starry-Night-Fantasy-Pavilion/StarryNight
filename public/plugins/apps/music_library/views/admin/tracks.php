<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>音乐管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>音乐管理</h2>
        <a href="/admin/music/tracks/add" class="btn btn-success">+ 添加音乐</a>
    </div>
    
    <div class="form-section mb-3">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" placeholder="音乐标题、艺术家或专辑" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">状态</label>
                    <select class="form-control" id="status">
                        <option value="">全部</option>
                        <option value="published" <?php echo ($_GET['status'] ?? '') === 'published' ? 'selected' : ''; ?>>已发布</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待审核</option>
                        <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>草稿</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">风格</label>
                    <select class="form-control" id="genre">
                        <option value="">全部</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($_GET['genre'] ?? '') === $genre ? 'selected' : ''; ?>><?php echo htmlspecialchars($genre); ?></option>
                        <?php endforeach; ?>
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
                    <th>音乐</th>
                    <th>艺术家</th>
                    <th>专辑</th>
                    <th>风格</th>
                    <th>时长</th>
                    <th>播放次数</th>
                    <th>状态</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tracks)): ?>
                    <?php foreach ($tracks as $track): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($track['cover_image'] ?? '/assets/common/images/default-cover.jpg'); ?>" alt="封面" style="width: 50px; height: 50px; border-radius: 6px; object-fit: cover;">
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($track['title']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($track['artist']); ?></td>
                            <td><?php echo htmlspecialchars($track['album'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($track['genre'] ?? ''); ?></td>
                            <td><?php echo $this->formatDuration($track['duration']); ?></td>
                            <td><?php echo number_format($track['plays']); ?></td>
                            <td>
                                <?php 
                                switch ($track['status']) {
                                    case 'published': 
                                        echo '<span class="badge bg-success">已发布</span>'; 
                                        break;
                                    case 'pending': 
                                        echo '<span class="badge bg-warning">待审核</span>'; 
                                        break;
                                    case 'draft': 
                                        echo '<span class="badge bg-danger">草稿</span>'; 
                                        break;
                                    default: 
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($track['status']) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($track['created_at'])); ?></td>
                            <td>
                                <a href="/admin/music/tracks/edit/<?php echo $track['id']; ?>" class="btn btn-sm btn-primary">编辑</a>
                                <button class="btn btn-sm btn-danger" onclick="deleteTrack(<?php echo $track['id']; ?>)">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">暂无音乐数据</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center" style="gap: 10px; margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo !empty($_GET['genre']) ? '&genre=' . urlencode($_GET['genre']) : ''; ?>" 
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
        const genre = document.getElementById('genre').value;
        
        let url = '?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (status) url += 'status=' + encodeURIComponent(status) + '&';
        if (genre) url += 'genre=' + encodeURIComponent(genre) + '&';
        
        window.location.href = url.slice(0, -1);
    }

    function deleteTrack(id) {
        if (confirm('确定要删除这首音乐吗？此操作不可撤销。')) {
            fetch('/api/v1/admin/music/tracks/' + id, {
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
