<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>排行榜管理</title>
    <link rel="stylesheet" href="/static/admin/css/plugin-modal.css">
    <style>
        .rankings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }
        .ranking-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
        }
        .ranking-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .ranking-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ranking-title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        .ranking-type {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 12px;
        }
        .ranking-items {
            padding: 12px 20px;
        }
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--glass-border);
        }
        .ranking-item:last-child {
            border-bottom: none;
        }
        .ranking-position {
            width: 28px;
            font-weight: 700;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.6);
        }
        .ranking-position.top {
            color: #ffc107;
        }
        .ranking-info {
            flex: 1;
            margin-left: 12px;
            min-width: 0;
        }
        .ranking-track {
            font-weight: 500;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ranking-artist {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }
        .ranking-stats {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        .ranking-plays {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }
        .ranking-change {
            font-size: 12px;
            font-weight: 500;
        }
        .change-up { color: #00ff80; }
        .change-down { color: #ff4757; }
        .change-same { color: rgba(255, 255, 255, 0.4); }
        .ranking-actions {
            padding: 12px 20px;
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .last-update {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>排行榜管理</h2>
        <a href="/admin/music/rankings/add" class="btn btn-success">+ 创建排行榜</a>
    </div>
    
    <?php if (!empty($rankings)): ?>
        <div class="rankings-grid">
            <?php foreach ($rankings as $ranking): ?>
                <div class="ranking-card">
                    <div class="ranking-header">
                        <div class="ranking-title"><?php echo htmlspecialchars($ranking['name']); ?></div>
                        <div class="ranking-type"><?php echo htmlspecialchars($ranking['type']); ?></div>
                    </div>
                    
                    <div class="ranking-items">
                        <?php if (!empty($ranking['items'])): ?>
                            <?php foreach ($ranking['items'] as $index => $item): ?>
                                <div class="ranking-item">
                                    <div class="ranking-position <?php echo $index < 3 ? 'top' : ''; ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="ranking-info">
                                        <div class="ranking-track"><?php echo htmlspecialchars($item['title']); ?></div>
                                        <div class="ranking-artist"><?php echo htmlspecialchars($item['artist']); ?></div>
                                    </div>
                                    <div class="ranking-stats">
                                        <div class="ranking-plays"><?php echo number_format($item['plays']); ?> 播放</div>
                                        <?php if (isset($item['change'])): ?>
                                            <div class="ranking-change <?php 
                                                if ($item['change'] > 0) echo 'change-up';
                                                elseif ($item['change'] < 0) echo 'change-down';
                                                else echo 'change-same';
                                            ?>">
                                                <?php 
                                                if ($item['change'] > 0) echo '↑' . abs($item['change']);
                                                elseif ($item['change'] < 0) echo '↓' . abs($item['change']);
                                                else echo '—';
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">暂无数据</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ranking-actions">
                        <div class="last-update">
                            更新: <?php echo date('Y-m-d H:i', strtotime($ranking['updated_at'])); ?>
                        </div>
                        <div class="d-flex" style="gap: 8px;">
                            <button class="btn btn-sm btn-primary" onclick="refreshRanking(<?php echo $ranking['id']; ?>)">刷新</button>
                            <a href="/admin/music/rankings/edit/<?php echo $ranking['id']; ?>" class="btn btn-sm btn-warning">编辑</a>
                            <button class="btn btn-sm btn-danger" onclick="deleteRanking(<?php echo $ranking['id']; ?>)">删除</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center text-muted" style="padding: 60px 0;">
            暂无排行榜，点击上方按钮创建
        </div>
    <?php endif; ?>
</div>

<script>
    function refreshRanking(id) {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '刷新中...';
        button.disabled = true;
        
        fetch('/api/v1/admin/music/rankings/' + id + '/refresh', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            button.textContent = originalText;
            button.disabled = false;
            
            if (data.success) {
                alert('排行榜已刷新');
                location.reload();
            } else {
                alert('刷新失败: ' + data.message);
            }
        })
        .catch(error => {
            button.textContent = originalText;
            button.disabled = false;
            alert('刷新失败: ' + error.message);
        });
    }
    
    function deleteRanking(id) {
        if (confirm('确定要删除这个排行榜吗？此操作不可撤销。')) {
            fetch('/api/v1/admin/music/rankings/' + id, {
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
