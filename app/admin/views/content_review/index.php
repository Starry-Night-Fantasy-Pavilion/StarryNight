<div class="review-container">
    <div class="dashboard-header-v2">
        <h1 class="dashboard-title-v2">审查队列</h1>
        <p class="dashboard-subtitle-v2">沉浸式内容合规性审查工作台</p>
    </div>

    <!-- 统计栏 -->
    <div class="review-stats-bar">
        <div class="review-stat-item dashboard-card-v2" style="display: flex; min-height: auto;">
            <div class="card-icon-v2 bg-user" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="position-relative z-2">
                <div class="small text-muted">待处理</div>
                <div class="fs-4 fw-bold text-white"><?= count(array_filter($data['items'], fn($i) => $i['status'] === 'pending')) ?></div>
            </div>
        </div>
        <div class="review-stat-item dashboard-card-v2" style="display: flex; min-height: auto;">
            <div class="card-icon-v2 bg-dau" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);">
                <i class="bi bi-layers"></i>
            </div>
            <div class="position-relative z-2">
                <div class="small text-muted">总队列</div>
                <div class="fs-4 fw-bold text-white"><?= count($data['items']) ?></div>
            </div>
        </div>
        <div class="review-stat-item dashboard-card-v2" style="display: flex; min-height: auto;">
            <div class="card-icon-v2 bg-coin" style="background: linear-gradient(135deg, #06b6d4, #22d3ee); box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);">
                <i class="bi bi-gear"></i>
            </div>
            <a href="/<?= $adminPrefix ?>/content-review/configs" class="stretched-link text-decoration-none position-relative z-2">
                <div class="small text-muted">流程配置</div>
                <div class="fw-bold" style="color: #5eead4;">前往设置 <i class="bi bi-arrow-right"></i></div>
            </a>
        </div>
    </div>

    <!-- 工具栏 (筛选 + 批量操作) -->
    <div class="dashboard-card-v2 mb-4 mx-auto review-toolbar" style="max-width: 1400px; display: block; min-height: auto;">
        <div class="card-body position-relative z-2 p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- 筛选表单 -->
                <form method="GET" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1" id="filterForm">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-transparent border-white border-opacity-10 text-muted"><i class="bi bi-filter"></i></span>
                        <select name="content_type" class="form-select bg-transparent border-white border-opacity-10 text-white" onchange="document.getElementById('filterForm').submit()">
                            <option value="" class="bg-dark">所有内容类型</option>
                            <option value="knowledge_base" <?= ($type === 'knowledge_base') ? 'selected' : '' ?> class="bg-dark">知识库资料</option>
                            <option value="ai_generated" <?= ($type === 'ai_generated') ? 'selected' : '' ?> class="bg-dark">AI 生成内容</option>
                            <option value="community" <?= ($type === 'community') ? 'selected' : '' ?> class="bg-dark">社区内容</option>
                            <option value="plugin" <?= ($type === 'plugin') ? 'selected' : '' ?> class="bg-dark">插件内容</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <span class="input-group-text bg-transparent border-white border-opacity-10 text-muted"><i class="bi bi-check-circle"></i></span>
                        <select name="status" class="form-select bg-transparent border-white border-opacity-10 text-white" onchange="document.getElementById('filterForm').submit()">
                            <option value="" class="bg-dark">所有状态</option>
                            <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?> class="bg-dark">待处理</option>
                            <option value="approved" <?= ($status === 'approved') ? 'selected' : '' ?> class="bg-dark">已通过</option>
                            <option value="rejected" <?= ($status === 'rejected') ? 'selected' : '' ?> class="bg-dark">已拒绝</option>
                            <option value="revision_requested" <?= ($status === 'revision_requested') ? 'selected' : '' ?> class="bg-dark">要求修改</option>
                        </select>
                    </div>
                    <button type="submit" class="d-none">筛选</button>
                </form>

                <!-- 操作按钮 -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" id="toggleBatchMode" style="border-color: rgba(94, 234, 212, 0.3); color: #5eead4;">
                        <i class="bi bi-list-check me-1"></i>批量模式
                    </button>
                </div>
            </div>

            <!-- 批量操作工具栏 (默认隐藏) -->
            <div id="batchToolbar" class="mt-3 pt-3 border-top border-white border-opacity-10" style="display: none;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-white small">
                        <div class="form-check d-inline-block me-3">
                            <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllSelection(this)">
                            <label class="form-check-label" for="selectAll">全选本页</label>
                        </div>
                        已选择 <span id="selectedCount" class="fw-bold text-info">0</span> 项
                    </div>
                    <div class="d-flex gap-2">
                        <select id="batchAction" class="form-select form-select-sm bg-transparent border-white border-opacity-10 text-white" style="width: 150px;">
                            <option value="" class="bg-dark">选择操作...</option>
                            <option value="approve" class="bg-dark">批量通过</option>
                            <option value="reject" class="bg-dark">批量拒绝</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary px-3" onclick="executeBatchAction()" style="background: linear-gradient(135deg, #06b6d4, #22d3ee); border: none;">执行</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 内容列表 -->
    <div class="review-list-container mx-auto" style="max-width: 1400px;">
        <?php if (!empty($data['items'])): ?>
            <div class="review-table-wrapper dashboard-card-v2">
                <table class="review-table">
                    <thead>
                        <tr>
                            <th class="batch-column" style="display: none; width: 40px;"></th>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 120px;">类型</th>
                            <th>内容信息</th>
                            <th style="width: 150px;">提交者</th>
                            <th style="width: 150px;">时间</th>
                            <th style="width: 100px;">状态</th>
                            <th style="width: 100px; text-align: right;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['items'] as $item): ?>
                        <tr class="review-row">
                            <td class="batch-column" style="display: none;">
                                <input type="checkbox" class="item-checkbox form-check-input" value="<?= $item['id'] ?>" onchange="updateSelection()">
                            </td>
                            <td class="text-muted">#<?= $item['id'] ?></td>
                            <td>
                                <span class="content-type-tag type-<?= $item['content_type'] ?>">
                                    <?php
                                    $typeNames = [
                                        'knowledge_base' => '知识库',
                                        'ai_generated' => 'AI生成',
                                        'community' => '社区',
                                        'plugin' => '插件'
                                    ];
                                    echo htmlspecialchars($typeNames[$item['content_type']] ?? $item['content_type']);
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="review-info">
                                    <div class="review-title-compact"><?= htmlspecialchars($item['title'] ?: '无标题内容') ?></div>
                                    <div class="review-preview-compact text-muted small text-truncate" style="max-width: 400px;"><?= htmlspecialchars($item['content_preview']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="bi bi-person-circle me-2 opacity-50"></i>
                                    <?= htmlspecialchars($item['submitter_name'] ?: '系统') ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <?= date('Y-m-d H:i', strtotime($item['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusConfig = [
                                    'pending' => ['class' => 'status-warning', 'icon' => 'bi-hourglass', 'text' => '待审'],
                                    'approved' => ['class' => 'status-success', 'icon' => 'bi-check-circle', 'text' => '已通过'],
                                    'rejected' => ['class' => 'status-danger', 'icon' => 'bi-x-circle', 'text' => '已拒绝'],
                                    'revision_requested' => ['class' => 'status-info', 'icon' => 'bi-pencil', 'text' => '待修改']
                                ];
                                $status = $statusConfig[$item['status']] ?? ['class' => 'status-secondary', 'icon' => 'bi-question-circle', 'text' => $item['status']];
                                ?>
                                <span class="status-badge <?= $status['class'] ?>">
                                    <i class="bi <?= $status['icon'] ?> me-1"></i><?= $status['text'] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="/<?= $adminPrefix ?>/content-review/details/<?= $item['id'] ?>" class="btn btn-sm btn-action-review">
                                    审查
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="dashboard-card-v2 text-center py-5">
                <div class="opacity-25 mb-3">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-white">暂无待处理内容</h5>
                <p class="text-muted small mb-0">所有的内容都已处理完毕，休息一下吧</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- 分页 -->
    <?php if ($data['totalPages'] > 1): ?>
    <div class="mt-5 d-flex justify-content-center">
        <nav>
            <ul class="pagination pagination-sm">
                <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                <li class="page-item <?= ($i === $data['page']) ? 'active' : '' ?>">
                    <a class="page-link bg-transparent border-white border-opacity-10 text-white" href="?page=<?= $i ?>&status=<?= $status ?>&content_type=<?= $type ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
let isBatchMode = false;
let selectedIds = [];

document.getElementById('toggleBatchMode').addEventListener('click', function() {
    if (!isBatchMode) {
        enterBatchMode();
    } else {
        exitBatchMode();
    }
});

function enterBatchMode() {
    isBatchMode = true;
    document.getElementById('batchToolbar').style.display = 'block';
    document.querySelectorAll('.batch-column').forEach(el => el.style.display = 'table-cell');
    
    const btn = document.getElementById('toggleBatchMode');
    btn.innerHTML = '<i class="bi bi-x-lg me-1"></i>退出批量';
    btn.classList.replace('btn-outline-info', 'btn-outline-danger');
    btn.style.borderColor = 'rgba(239, 68, 68, 0.3)';
    btn.style.color = '#ef4444';
}

function exitBatchMode() {
    isBatchMode = false;
    document.getElementById('batchToolbar').style.display = 'none';
    document.querySelectorAll('.batch-column').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    
    const btn = document.getElementById('toggleBatchMode');
    btn.innerHTML = '<i class="bi bi-list-check me-1"></i>批量模式';
    btn.classList.replace('btn-outline-danger', 'btn-outline-info');
    btn.style.borderColor = 'rgba(94, 234, 212, 0.3)';
    btn.style.color = '#5eead4';
    
    selectedIds = [];
    updateSelection();
}

function toggleAllSelection(checkbox) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    selectedIds = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('selectedCount').textContent = selectedIds.length;
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectAllCb = document.getElementById('selectAll');
    if (allCheckboxes.length > 0) {
        selectAllCb.checked = checkboxes.length === allCheckboxes.length;
        selectAllCb.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
    }
}

function executeBatchAction() {
    const action = document.getElementById('batchAction').value;
    if (!action || selectedIds.length === 0) {
        alert('请选择操作和至少一项内容');
        return;
    }
    
    if (!confirm(`确定要批量处理这 ${selectedIds.length} 项内容吗？`)) return;
    
    const formData = new FormData();
    formData.append('ids', selectedIds.join(','));
    formData.append('action', action);
    
    fetch('/<?= $adminPrefix ?>/content-review/batch-review', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('操作失败: ' + data.message);
        }
    });
}
</script>
