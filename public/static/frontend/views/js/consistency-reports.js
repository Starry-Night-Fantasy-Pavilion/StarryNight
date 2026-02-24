var currentReportId = null;

function toggleFilters() {
    var filtersGrid = document.getElementById('filtersGrid');
    if (!filtersGrid) return;
    filtersGrid.style.display = filtersGrid.style.display === 'none' ? 'grid' : 'none';
}

function applyFilters() {
    var dateRangeEl = document.getElementById('dateRange');
    var statusEl = document.getElementById('statusFilter');
    var typeEl = document.getElementById('typeFilter');
    var searchEl = document.getElementById('searchInput');
    
    var dateRange = dateRangeEl ? dateRangeEl.value : null;
    var status = statusEl ? statusEl.value : null;
    var type = typeEl ? typeEl.value : null;
    var search = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase();

    var rows = document.querySelectorAll('.report-row');

    rows.forEach(function(row) {
        var show = true;

        // 当前只按搜索文本过滤，后续可以扩展 dateRange/status/type 的实际逻辑
        if (search) {
            var titleEl = row.querySelector('.report-title');
            var descEl = row.querySelector('.report-description');
            var title = (titleEl && titleEl.textContent ? titleEl.textContent : '').toLowerCase();
            var description = (descEl && descEl.textContent ? descEl.textContent : '').toLowerCase();
            show = title.indexOf(search) !== -1 || description.indexOf(search) !== -1;
        }

        row.style.display = show ? '' : 'none';
    });
}

function toggleSelectAll() {
    var selectAll = document.getElementById('selectAll');
    var checkboxes = document.querySelectorAll('.report-checkbox');
    if (!selectAll) return;
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function viewReport(id) {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    currentReportId = id;

    fetch(base + '/reports/' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReportDetails(data.report);
            } else {
                alert('获取报告详情失败：' + data.message);
            }
        })
        .catch(error => {
            alert('获取报告详情失败：' + error.message);
        });
}

function displayReportDetails(report) {
    var modalTitle = document.getElementById('modalTitle');
    var modalBody = document.getElementById('modalBody');
    if (!modalTitle || !modalBody) return;

    modalTitle.textContent = report.title;

    modalBody.innerHTML = `
        <div class="report-details">
            <div class="detail-section">
                <h4>基本信息</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>检查类型:</label>
                        <span>${getCheckTypeLabel(report.check_type)}</span>
                    </div>
                    <div class="detail-item">
                        <label>整体状态:</label>
                        <span class="status-badge status-${report.overall_status}">${getStatusLabel(report.overall_status)}</span>
                    </div>
                    <div class="detail-item">
                        <label>冲突数量:</label>
                        <span>${report.conflict_count}</span>
                    </div>
                    <div class="detail-item">
                        <label>平均相似度:</label>
                        <span>${(report.avg_similarity * 100)}%</span>
                    </div>
                    <div class="detail-item">
                        <label>检查耗时:</label>
                        <span>${report.check_time}秒</span>
                    </div>
                    <div class="detail-item">
                        <label>创建时间:</label>
                        <span>${new Date(report.created_at).toLocaleString()}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>检查内容</h4>
                <div class="content-box">
                    ${report.check_content}
                </div>
            </div>
            
            ${report.conflicts && report.conflicts.length > 0 ? `
            <div class="detail-section">
                <h4>冲突详情</h4>
                <div class="conflicts-list">
                    ${report.conflicts.map(conflict => `
                        <div class="conflict-item severity-${conflict.severity}">
                            <div class="conflict-header">
                                <span class="conflict-type">${getConflictTypeLabel(conflict.type)}</span>
                                <span class="conflict-severity">${getSeverityLabel(conflict.severity)}</span>
                                <span class="conflict-score">相似度: ${(conflict.similarity * 100)}%</span>
                            </div>
                            <div class="conflict-content">
                                <div class="conflict-original">
                                    <h5>原文内容</h5>
                                    <p>${conflict.original_content}</p>
                                </div>
                                <div class="conflict-core">
                                    <h5>冲突设定</h5>
                                    <p><strong>${conflict.core_setting_title}</strong></p>
                                    <p>${conflict.core_setting_content}</p>
                                </div>
                            </div>
                            <div class="conflict-suggestion">
                                <h5>修复建议</h5>
                                <p>${conflict.suggestion}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            ` : ''}
        </div>
    `;

    var reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.style.display = 'block';
    }
}

function downloadReport(id) {
    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    window.open(base + '/reports/' + id + '/download', '_blank');
}

function downloadCurrentReport() {
    if (currentReportId) {
        downloadReport(currentReportId);
    }
}

function deleteReport(id) {
    if (!confirm('确定要删除这个报告吗？此操作不可撤销。')) return;

    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    fetch(base + '/reports/' + id, {
        method: 'DELETE'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            alert('删除失败：' + error.message);
        });
}

function deleteSelected() {
    var selected = document.querySelectorAll('.report-checkbox:checked');
    if (selected.length === 0) {
        alert('请先选择要删除的报告');
        return;
    }

    if (!confirm(`确定要删除选中的 ${selected.length} 个报告吗？此操作不可撤销。`)) {
        return;
    }

    var ids = Array.from(selected).map(checkbox => checkbox.value);

    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    fetch(base + '/reports/batch-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('删除失败：' + data.message);
            }
        })
        .catch(error => {
            alert('删除失败：' + error.message);
        });
}

function exportReports() {
    var selected = document.querySelectorAll('.report-checkbox:checked');
    var ids = selected.length > 0 ? Array.from(selected).map(checkbox => checkbox.value) : [];

    var container = document.querySelector('.consistency-check-container');
    var adminPrefixRaw = container && container.dataset && container.dataset.adminPrefix
        ? container.dataset.adminPrefix
        : '';
    var adminPrefix = (adminPrefixRaw || '').replace(/^\/+|\/+$/g, '');
    var base = adminPrefix ? `/${adminPrefix}/consistency` : '/consistency';

    var url = ids.length > 0
        ? base + '/reports/export?ids=' + ids.join(',')
        : base + '/reports/export';

    window.open(url, '_blank');
}

function previousPage() {
    var current = parseInt(document.body.dataset.currentPage || '1', 10);
    var page = current - 1;
    if (page >= 1) {
        var url = new URL(window.location.href);
        url.searchParams.set('page', String(page));
        window.location.href = url.toString();
    }
}

function nextPage() {
    var current = parseInt(document.body.dataset.currentPage || '1', 10);
    var total = parseInt(document.body.dataset.totalPages || '1', 10);
    var page = current + 1;
    if (page <= total) {
        var url = new URL(window.location.href);
        url.searchParams.set('page', String(page));
        window.location.href = url.toString();
    }
}

function closeModal() {
    var reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.style.display = 'none';
    }
    currentReportId = null;
}

window.addEventListenerfunction('click', (event) {
    var modal = document.getElementById('reportModal');
    if (modal && event.target === modal) {
        closeModal();
    }
});

function getCheckTypeLabel(type) {
    var labels = {
        'full': '全面检查',
        'worldview': '世界观检查',
        'character': '角色检查',
        'event': '事件检查',
        'rule': '规则检查'
    };
    return labels[type] || type;
}

function getStatusLabel(status) {
    var labels = {
        'success': '通过',
        'warning': '警告',
        'error': '冲突'
    };
    return labels[status] || status;
}

function getConflictTypeLabel(type) {
    var labels = {
        'worldview': '世界观',
        'character': '角色',
        'event': '事件',
        'rule': '规则'
    };
    return labels[type] || type;
}

function getSeverityLabel(severity) {
    var labels = {
        'low': '低',
        'medium': '中',
        'high': '高',
        'critical': '严重'
    };
    return labels[severity] || severity;
}

