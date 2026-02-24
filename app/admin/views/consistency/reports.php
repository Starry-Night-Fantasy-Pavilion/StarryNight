<?php
$adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '');

// 视图内简单工具函数，避免使用 $this 导致 500
if (!function_exists('consistency_truncate_text')) {
    function consistency_truncate_text(string $text, int $length = 100): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
}

if (!function_exists('consistency_get_check_type_label')) {
    function consistency_get_check_type_label(string $type): string
    {
        $labels = [
            'full'      => '全面检�?,
            'worldview' => '世界观检�?,
            'character' => '角色检�?,
            'event'     => '事件检�?,
            'rule'      => '规则检�?,
        ];
        return $labels[$type] ?? $type;
    }
}

if (!function_exists('consistency_get_status_label')) {
    function consistency_get_status_label(string $status): string
    {
        $labels = [
            'success' => '通过',
            'warning' => '警告',
            'error'   => '冲突',
        ];
        return $labels[$status] ?? $status;
    }
}
?>

<link rel="stylesheet" href="/static/frontend/views/css/consistency-reports.css?v=<?= time() ?>">

<div class="consistency-check-container" data-admin-prefix="<?= htmlspecialchars(trim((string)$adminPrefix, '/'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="page-header">
        <h1 class="page-title">检查报�?/h1>
        <p class="page-description">查看和管理一致性检查的历史报告</p>
    </div>

    <div class="consistency-nav-tabs">
        <ul class="nav-tabs">
            <li class="nav-tab <?= ($currentPage === 'consistency-config') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/config">系统配置</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-core-settings') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/core-settings">核心设定</a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-check') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/check">一致性检�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-reports') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/reports">检查报�?/a>
            </li>
            <li class="nav-tab <?= ($currentPage === 'consistency-analytics') ? 'active' : '' ?>">
                <a href="/<?= $adminPrefix ?>/consistency/analytics">分析统计</a>
            </li>
        </ul>
    </div>

    <div class="reports-content">
        <div class="filters-section">
            <div class="filters-header">
                <h2>筛选条�?/h2>
                <button class="btn btn-outline btn-sm" onclick="toggleFilters()">
                    <i class="icon">🔍</i> 高级筛�?                </button>
            </div>
            
            <div class="filters-grid" id="filtersGrid">
                <div class="form-group">
                    <label for="dateRange" class="form-label">时间范围</label>
                    <select id="dateRange" class="form-select" onchange="applyFilters()">
                        <option value="">全部时间</option>
                        <option value="today">今天</option>
                        <option value="week">最�?�?/option>
                        <option value="month">最�?0�?/option>
                        <option value="quarter">最�?个月</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="statusFilter" class="form-label">检查状�?/label>
                    <select id="statusFilter" class="form-select" onchange="applyFilters()">
                        <option value="">全部状�?/option>
                        <option value="success">通过</option>
                        <option value="warning">警告</option>
                        <option value="error">冲突</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="typeFilter" class="form-label">检查类�?/label>
                    <select id="typeFilter" class="form-select" onchange="applyFilters()">
                        <option value="">全部类型</option>
                        <option value="full">全面检�?/option>
                        <option value="worldview">世界观检�?/option>
                        <option value="character">角色检�?/option>
                        <option value="event">事件检�?/option>
                        <option value="rule">规则检�?/option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="searchInput" class="form-label">搜索</label>
                    <input type="text" id="searchInput" class="form-input" placeholder="搜索报告标题或内�?.." onkeyup="applyFilters()">
                </div>
            </div>
        </div>

        <div class="reports-stats">
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="icon">📊</i>
                    </div>
                    <div class="stat-content">
                        <h3>总报告数</h3>
                        <p class="stat-value"><?= $stats['total_reports'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon status-success">
                        <i class="icon">�?/i>
                    </div>
                    <div class="stat-content">
                        <h3>通过检�?/h3>
                        <p class="stat-value"><?= $stats['passed_reports'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon status-warning">
                        <i class="icon">⚠️</i>
                    </div>
                    <div class="stat-content">
                        <h3>警告报告</h3>
                        <p class="stat-value"><?= $stats['warning_reports'] ?? 0 ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon status-error">
                        <i class="icon">�?/i>
                    </div>
                    <div class="stat-content">
                        <h3>冲突报告</h3>
                        <p class="stat-value"><?= $stats['error_reports'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="reports-list">
            <div class="list-header">
                <h2>报告列表</h2>
                <div class="list-actions">
                    <button class="btn btn-secondary" onclick="exportReports()">
                        <i class="icon">📥</i> 导出报告
                    </button>
                    <button class="btn btn-outline" onclick="deleteSelected()">
                        <i class="icon">🗑�?/i> 删除选中
                    </button>
                </div>
            </div>

            <div class="reports-table-container">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>报告标题</th>
                            <th>检查类�?/th>
                            <th>状�?/th>
                            <th>冲突�?/th>
                            <th>相似�?/th>
                            <th>检查时�?/th>
                            <th>创建时间</th>
                            <th class="actions-column">操作</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <?php foreach ($reports as $report): ?>
                        <tr class="report-row" data-id="<?= $report['id'] ?>">
                            <td class="checkbox-column">
                                <input type="checkbox" class="report-checkbox" value="<?= $report['id'] ?>">
                            </td>
                            <td class="title-column">
                                <div class="report-title"><?= htmlspecialchars($report['title'] ?? '未命名报�?) ?></div>
                                <div class="report-description"><?= consistency_truncate_text(htmlspecialchars($report['description'] ?? ''), 100) ?></div>
                            </td>
                            <td class="type-column">
                                <span class="type-badge type-<?= $report['check_type'] ?>">
                                    <?= consistency_get_check_type_label($report['check_type']) ?>
                                </span>
                            </td>
                            <td class="status-column">
                                <span class="status-badge status-<?= $report['overall_status'] ?>">
                                    <?= consistency_get_status_label($report['overall_status']) ?>
                                </span>
                            </td>
                            <td class="conflicts-column">
                                <span class="conflict-count"><?= $report['conflict_count'] ?></span>
                            </td>
                            <td class="similarity-column">
                                <div class="similarity-bar">
                                    <div class="similarity-fill" style="width: <?= ($report['avg_similarity'] ?? 0) * 100 ?>%"></div>
                                    <span class="similarity-text"><?= ($report['avg_similarity'] ?? 0) * 100 ?>%</span>
                                </div>
                            </td>
                            <td class="check-time-column"><?= $report['check_time'] ?>s</td>
                            <td class="created-time-column"><?= date('Y-m-d H:i', strtotime($report['created_at'])) ?></td>
                            <td class="actions-column">
                                <div class="action-buttons">
                                    <button class="btn-icon" onclick="viewReport(<?= $report['id'] ?>)" title="查看">
                                        <i class="icon">👁�?/i>
                                    </button>
                                    <button class="btn-icon" onclick="downloadReport(<?= $report['id'] ?>)" title="下载">
                                        <i class="icon">📥</i>
                                    </button>
                                    <button class="btn-icon" onclick="deleteReport(<?= $report['id'] ?>)" title="删除">
                                        <i class="icon">🗑�?/i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>暂无报告</h3>
                    <p>执行一致性检查后，报告将显示在这�?/p>
                    <a href="/<?= $adminPrefix ?>/consistency/check" class="btn btn-primary">
                        <i class="icon">🔍</i> 开始检�?                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($reports)): ?>
            <div class="pagination">
                <button class="btn btn-outline" onclick="previousPage()" <?= $currentPageNum <= 1 ? 'disabled' : '' ?>>
                    <i class="icon">⬅️</i> 上一�?                </button>
                <span class="page-info">
                    �?<?= $currentPageNum ?> 页，�?<?= $totalPages ?> �?                </span>
                <button class="btn btn-outline" onclick="nextPage()" <?= $currentPageNum >= $totalPages ? 'disabled' : '' ?>>
                    下一�?<i class="icon">➡️</i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 报告详情模态框 -->
<div id="reportModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="modalTitle">报告详情</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- 报告详情内容将在这里动态加�?-->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="downloadCurrentReport()">
                <i class="icon">📥</i> 下载报告
            </button>
            <button class="btn btn-outline" onclick="closeModal()">关闭</button>
        </div>
    </div>
</div>

<script>
document.body.dataset.currentPage = '<?= (int)$currentPageNum ?>';
document.body.dataset.totalPages = '<?= (int)$totalPages ?>';
</script>
<script src="/static/frontend/views/js/consistency-reports.js?v=<?= time() ?>"></script>
