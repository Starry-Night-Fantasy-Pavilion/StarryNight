<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通知栏管理 - 星夜阁</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .notice-item {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        .notice-item.high-priority {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .notice-item.medium-priority {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .notice-item.low-priority {
            border-left-color: #28a745;
            background: #f8fff8;
        }
        .priority-badge {
            font-size: 0.75rem;
        }
        .status-enabled {
            color: #28a745;
        }
        .status-disabled {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-megaphone"></i> 通知栏管理</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
                        <i class="bi bi-plus-circle"></i> 新建通知
                    </button>
                </div>

                <!-- 筛选器 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">状态</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">全部</option>
                                    <option value="enabled" <?= $status == 'enabled' ? 'selected' : '' ?>>启用</option>
                                    <option value="disabled" <?= $status == 'disabled' ? 'selected' : '' ?>>禁用</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="lang" class="form-label">语言</label>
                                <select name="lang" id="lang" class="form-select">
                                    <option value="">全部</option>
                                    <option value="zh-CN" <?= $lang == 'zh-CN' ? 'selected' : '' ?>>简体中文</option>
                                    <option value="en-US" <?= $lang == 'en-US' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-funnel"></i> 筛选
                                    </button>
                                    <a href="?page=1" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> 重置
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 通知列表 -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($notices)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">暂无通知记录</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>内容</th>
                                            <th>优先级</th>
                                            <th>语言</th>
                                            <th>状态</th>
                                            <th>显示时间</th>
                                            <th>创建时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notices as $notice): ?>
                                            <tr>
                                                <td>
                                                    <div class="notice-item <?= getPriorityClass($notice['priority']) ?>">
                                                        <div><?= htmlspecialchars($notice['content']) ?></div>
                                                        <?php if (!empty($notice['link'])): ?>
                                                            <small class="text-muted">
                                                                <i class="bi bi-link-45deg"></i>
                                                                <a href="<?= htmlspecialchars($notice['link']) ?>" target="_blank">
                                                                    <?= htmlspecialchars($notice['link']) ?>
                                                                </a>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge priority-badge bg-<?= getPriorityBadgeClass($notice['priority']) ?>">
                                                        <?= getPriorityText($notice['priority']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $notice['lang'] ?></td>
                                                <td>
                                                    <span class="status-<?= $notice['status'] ?>">
                                                        <i class="bi bi-<?= $notice['status'] == 'enabled' ? 'check-circle' : 'x-circle' ?>"></i>
                                                        <?= $notice['status'] == 'enabled' ? '启用' : '禁用' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($notice['display_from']): ?>
                                                        <?= date('Y-m-d H:i', strtotime($notice['display_from'])) ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                    <br>
                                                    <?php if ($notice['display_to']): ?>
                                                        <small class="text-muted">至 <?= date('Y-m-d H:i', strtotime($notice['display_to'])) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('Y-m-d H:i', strtotime($notice['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                onclick="editNotice(<?= $notice['id'] ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                                onclick="toggleNotice(<?= $notice['id'] ?>)">
                                                            <i class="bi bi-<?= $notice['status'] == 'enabled' ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                onclick="deleteNotice(<?= $notice['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 分页 -->
                            <?php if ($total > $perPage): ?>
                                <nav aria-label="通知列表分页">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php
                                        $totalPages = ceil($total / $perPage);
                                        $currentPage = $page;
                                        
                                        // 上一页
                                        if ($currentPage > 1):
                                        ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $currentPage - 1 ?>&status=<?= urlencode($status) ?>&lang=<?= urlencode($lang) ?>">
                                                    <i class="bi bi-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- 页码 -->
                                        <?php
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($totalPages, $currentPage + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&lang=<?= urlencode($lang) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        // 下一页
                                        <?php if ($currentPage < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $currentPage + 1 ?>&status=<?= urlencode($status) ?>&lang=<?= urlencode($lang) ?>">
                                                    <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 创建通知模态框 -->
    <div class="modal fade" id="createNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新建通知</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createNoticeForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="content" class="form-label">通知内容 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="3" required maxlength="500"></textarea>
                            <div class="form-text">最多500个字符</div>
                        </div>
                        <div class="mb-3">
                            <label for="link" class="form-label">跳转链接</label>
                            <input type="url" class="form-control" id="link" name="link" placeholder="https://example.com">
                            <div class="form-text">用户点击通知时跳转的链接（可选）</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">优先级</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="1">低</option>
                                    <option value="2">较低</option>
                                    <option value="3" selected>普通</option>
                                    <option value="4">较高</option>
                                    <option value="5">高</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lang" class="form-label">语言</label>
                                <select class="form-select" id="lang" name="lang">
                                    <option value="zh-CN" selected>简体中文</option>
                                    <option value="en-US">English</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">显示开始时间</label>
                                <input type="datetime-local" class="form-control" id="start_time" name="start_time">
                                <div class="form-text">留空表示立即显示</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">显示结束时间</label>
                                <input type="datetime-local" class="form-control" id="end_time" name="end_time">
                                <div class="form-text">留空表示永久显示</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">状态</label>
                            <select class="form-select" id="status" name="status">
                                <option value="enabled" selected>启用</option>
                                <option value="disabled">禁用</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">创建通知</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑通知模态框 -->
    <div class="modal fade" id="editNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑通知</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editNoticeForm">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">通知内容 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_content" name="content" rows="3" required maxlength="500"></textarea>
                            <div class="form-text">最多500个字符</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_link" class="form-label">跳转链接</label>
                            <input type="url" class="form-control" id="edit_link" name="link" placeholder="https://example.com">
                            <div class="form-text">用户点击通知时跳转的链接（可选）</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_priority" class="form-label">优先级</label>
                                <select class="form-select" id="edit_priority" name="priority">
                                    <option value="1">低</option>
                                    <option value="2">较低</option>
                                    <option value="3">普通</option>
                                    <option value="4">较高</option>
                                    <option value="5">高</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_lang" class="form-label">语言</label>
                                <select class="form-select" id="edit_lang" name="lang">
                                    <option value="zh-CN">简体中文</option>
                                    <option value="en-US">English</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_start_time" class="form-label">显示开始时间</label>
                                <input type="datetime-local" class="form-control" id="edit_start_time" name="start_time">
                                <div class="form-text">留空表示立即显示</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_end_time" class="form-label">显示结束时间</label>
                                <input type="datetime-local" class="form-control" id="edit_end_time" name="end_time">
                                <div class="form-text">留空表示永久显示</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">状态</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="enabled">启用</option>
                                <option value="disabled">禁用</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">更新通知</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 创建通知
        document.getElementById('createNoticeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/notice-bar/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('通知创建成功');
                    location.reload();
                } else {
                    alert('创建失败: ' + result.message);
                }
            })
            .catch(error => {
                alert('请求失败: ' + error.message);
            });
        });

        // 编辑通知
        function editNotice(id) {
            fetch('/notice-bar/detail?id=' + id)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const notice = result.data;
                        document.getElementById('edit_id').value = notice.id;
                        document.getElementById('edit_content').value = notice.content;
                        document.getElementById('edit_link').value = notice.link || '';
                        document.getElementById('edit_priority').value = notice.priority;
                        document.getElementById('edit_lang').value = notice.lang;
                        document.getElementById('edit_start_time').value = notice.display_from ? 
                            notice.display_from.slice(0, 16) : '';
                        document.getElementById('edit_end_time').value = notice.display_to ? 
                            notice.display_to.slice(0, 16) : '';
                        document.getElementById('edit_status').value = notice.status;
                        
                        new bootstrap.Modal(document.getElementById('editNoticeModal')).show();
                    } else {
                        alert('获取通知详情失败: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('请求失败: ' + error.message);
                });
        }

        // 更新通知
        document.getElementById('editNoticeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/notice-bar/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('通知更新成功');
                    location.reload();
                } else {
                    alert('更新失败: ' + result.message);
                }
            })
            .catch(error => {
                alert('请求失败: ' + error.message);
            });
        });

        // 切换状态
        function toggleNotice(id) {
            if (confirm('确定要切换这个通知的状态吗？')) {
                fetch('/notice-bar/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('操作失败: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('请求失败: ' + error.message);
                });
            }
        }

        // 删除通知
        function deleteNotice(id) {
            if (confirm('确定要删除这个通知吗？此操作不可恢复。')) {
                fetch('/notice-bar/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('通知删除成功');
                        location.reload();
                    } else {
                        alert('删除失败: ' + result.message);
                    }
                })
                .catch(error => {
                    alert('请求失败: ' + error.message);
                });
            }
        }
    </script>

    <?php
    // 辅助函数
    function getPriorityClass($priority) {
        if ($priority >= 4) return 'high-priority';
        if ($priority >= 3) return 'medium-priority';
        return 'low-priority';
    }

    function getPriorityBadgeClass($priority) {
        if ($priority >= 4) return 'danger';
        if ($priority >= 3) return 'warning';
        return 'success';
    }

    function getPriorityText($priority) {
        $texts = [1 => '低', 2 => '较低', 3 => '普通', 4 => '较高', 5 => '高'];
        return $texts[$priority] ?? '未知';
    }
    ?>
</body>
</html>