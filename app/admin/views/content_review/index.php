<div class="dashboard-v2">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">审查队列</h1>
        <p class="dashboard-subtitle-v2">管理需要进行合规性审查的内容</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">内容类型</label>
                    <select name="content_type" class="form-select">
                        <option value="">全部</option>
                        <option value="knowledge_base" <?= ($type === 'knowledge_base') ? 'selected' : '' ?>>知识库资料</option>
                        <option value="ai_generated" <?= ($type === 'ai_generated') ? 'selected' : '' ?>>AI 生成内容</option>
                        <option value="community" <?= ($type === 'community') ? 'selected' : '' ?>>社区内容</option>
                        <option value="plugin" <?= ($type === 'plugin') ? 'selected' : '' ?>>插件内容</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-select">
                        <option value="">全部</option>
                        <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?>>待处理</option>
                        <option value="approved" <?= ($status === 'approved') ? 'selected' : '' ?>>已通过</option>
                        <option value="rejected" <?= ($status === 'rejected') ? 'selected' : '' ?>>已拒绝</option>
                        <option value="revision_requested" <?= ($status === 'revision_requested') ? 'selected' : '' ?>>要求修改</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">筛选</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="/<?= $adminPrefix ?>/content-review/configs" class="btn btn-outline-secondary w-100">审查配置</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 批量操作工具栏 -->
    <div class="card mb-4" id="batchToolbar" style="display: none;">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span id="selectedCount" class="badge bg-primary">0</span>
                    <span class="ms-2">项已选择</span>
                </div>
                <div class="d-flex gap-2">
                    <select id="batchAction" class="form-select form-select-sm" style="width: auto;">
                        <option value="">选择操作</option>
                        <option value="approve">批量通过</option>
                        <option value="reject">批量拒绝</option>
                        <option value="request_revision">批量要求修改</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" onclick="executeBatchAction()">执行</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">取消选择</button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .content-review-table {
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-collapse: collapse !important;
    }
    .content-review-table th,
    .content-review-table td {
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: rgba(255, 255, 255, 0.9);
        padding: 12px 16px;
    }
    .content-review-table thead {
        background: rgba(255, 255, 255, 0.08);
    }
    .content-review-table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.2) !important;
        background: rgba(255, 255, 255, 0.05);
    }
    .content-review-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
    }
    /* 确保空状态也显示边框 */
    .content-review-table td[colspan] {
        text-align: center;
        padding: 40px !important;
        color: rgba(255, 255, 255, 0.4);
    }
    </style>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0 content-review-table">
                <thead>
                    <tr>
                        <th width="50" class="text-center">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                        </th>
                        <th width="80">ID</th>
                        <th width="120">类型</th>
                        <th>标题/预览</th>
                        <th width="150">提交者</th>
                        <th width="100">状态</th>
                        <th width="160">提交时间</th>
                        <th width="100" class="text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="item-checkbox" value="<?= $item['id'] ?>" onchange="updateSelection()">
                        </td>
                        <td>#<?= $item['id'] ?></td>
                        <td>
                            <?php
                            $typeNames = [
                                'knowledge_base' => '知识库',
                                'ai_generated' => 'AI生成',
                                'community' => '社区',
                                'plugin' => '插件'
                            ];
                            $typeName = $typeNames[$item['content_type']] ?? $item['content_type'];
                            ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($typeName) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold mb-1"><?= htmlspecialchars($item['title'] ?: '无标题') ?></div>
                            <div class="small text-muted text-truncate" style="max-width: 400px;">
                                <?= htmlspecialchars($item['content_preview']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle text-muted me-2 fs-5"></i>
                                <span><?= htmlspecialchars($item['submitter_name'] ?: '系统/游客') ?></span>
                            </div>
                        </td>
                        <td>
                            <?php
                            $statusConfig = [
                                'pending' => ['class' => 'bg-warning text-dark', 'text' => '待处理'],
                                'approved' => ['class' => 'bg-success', 'text' => '已通过'],
                                'rejected' => ['class' => 'bg-danger', 'text' => '已拒绝'],
                                'revision_requested' => ['class' => 'bg-info text-dark', 'text' => '要求修改']
                            ];
                            $status = $statusConfig[$item['status']] ?? ['class' => 'bg-secondary', 'text' => $item['status']];
                            ?>
                            <span class="badge <?= $status['class'] ?>">
                                <?= htmlspecialchars($status['text']) ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= $item['created_at'] ?></td>
                        <td class="text-center">
                            <a href="/<?= $adminPrefix ?>/content-review/details/<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>审查
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($data['items'])): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            暂无待处理内容
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($data['totalPages'] > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                    <li class="page-item <?= ($i === $data['page']) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&content_type=<?= $type ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let selectedIds = [];

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    selectedIds = Array.from(checkboxes).map(cb => cb.value);
    
    const toolbar = document.getElementById('batchToolbar');
    const countSpan = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (selectedIds.length > 0) {
        toolbar.style.display = 'block';
        countSpan.textContent = selectedIds.length;
    } else {
        toolbar.style.display = 'none';
    }
    
    // 更新全选状态
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && selectedIds.length === allCheckboxes.length;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    selectedIds = [];
    updateSelection();
}

function executeBatchAction() {
    const action = document.getElementById('batchAction').value;
    
    if (!action) {
        alert('请选择操作类型');
        return;
    }
    
    if (selectedIds.length === 0) {
        alert('请至少选择一项');
        return;
    }
    
    if (!confirm(`确定要对选中的 ${selectedIds.length} 项执行"${getActionName(action)}"操作吗？`)) {
        return;
    }
    
    const comment = prompt('请输入审核备注（可选）：') || '';
    
    const formData = new FormData();
    formData.append('ids', selectedIds.join(','));
    formData.append('action', action);
    formData.append('comment', comment);
    
    fetch('/<?= $adminPrefix ?>/content-review/batch-review', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('操作失败：' + (data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('操作失败，请稍后重试');
    });
}

function getActionName(action) {
    const names = {
        'approve': '批量通过',
        'reject': '批量拒绝',
        'request_revision': '批量要求修改'
    };
    return names[action] || action;
}
</script>
