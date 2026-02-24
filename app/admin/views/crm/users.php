<?php
// File: app/admin/views/crm/users.php
// 用户管理界面 - 现代化卡片网格布局设计

// Helper function for sorting links
function get_sort_link($field, $currentSort, $currentOrder) {
    $order = ($currentSort === $field && $currentOrder === 'asc') ? 'desc' : 'asc';
    $queryParams = http_build_query(array_merge($_GET, ['sort_by' => $field, 'sort_order' => $order]));
    return "?" . $queryParams;
}
?>

<?php $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/'); ?>

<div class="crm-page-v2" data-page-type="users">
    <!-- 页面头部 -->
    <div class="crm-header-v2">
        <div class="crm-header-main">
            <div class="crm-title-group">
                <div class="crm-title-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="crm-title-content">
                    <h1 class="crm-page-title">用户管理</h1>
                    <p class="crm-page-desc">管理系统所有注册用户、会员等级及账户状态</p>
                </div>
            </div>
            <div class="crm-header-actions">
                <button type="button" class="crm-btn-v2 crm-btn-primary-v2" onclick="showAddUserModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    添加用户
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-glass" onclick="toggleAdvancedFilters()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    <span id="filterToggleText">筛选</span>
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-glass" onclick="exportUsers()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    导出
                </button>
            </div>
        </div>
        
        <!-- 高级筛选面板 -->
        <div id="advancedFilters" class="crm-filters-panel-v2" style="display:none;">
            <form method="GET" action="" id="filterForm" class="crm-filters-form-v2">
                <div class="crm-filters-grid-v2">
                    <div class="crm-filter-field">
                        <label class="crm-filter-label-v2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            搜索
                        </label>
                        <input type="text" class="crm-input-v2" placeholder="ID、昵称、邮箱..." name="search" value="<?php echo htmlspecialchars($searchTerm ?? '', ENT_QUOTES); ?>">
                    </div>
                    <div class="crm-filter-field">
                        <label class="crm-filter-label-v2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            状态
                        </label>
                        <select class="crm-select-v2" name="filter_status">
                            <option value="">全部状态</option>
                            <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>正常</option>
                            <option value="disabled" <?php echo (($_GET['filter_status'] ?? '') === 'disabled') ? 'selected' : ''; ?>>禁用</option>
                            <option value="frozen" <?php echo (($_GET['filter_status'] ?? '') === 'frozen') ? 'selected' : ''; ?>>冻结</option>
                            <option value="deleted" <?php echo (($_GET['filter_status'] ?? '') === 'deleted') ? 'selected' : ''; ?>>已删除</option>
                        </select>
                    </div>
                    <div class="crm-filter-field">
                        <label class="crm-filter-label-v2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            会员等级
                        </label>
                        <select class="crm-select-v2" name="filter_membership_level">
                            <option value="">全部等级</option>
                            <option value="none" <?php echo (($_GET['filter_membership_level'] ?? '') === 'none') ? 'selected' : ''; ?>>非会员</option>
                            <?php if (!empty($membershipLevels)): ?>
                                <?php foreach ($membershipLevels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>" <?php echo (($_GET['filter_membership_level'] ?? '') == $level['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['name'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="crm-filter-field">
                        <label class="crm-filter-label-v2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            注册时间
                        </label>
                        <div class="crm-date-range">
                            <input type="date" class="crm-input-v2 crm-input-date" name="filter_created_from" value="<?php echo htmlspecialchars($_GET['filter_created_from'] ?? '', ENT_QUOTES); ?>" placeholder="开始">
                            <span class="crm-date-sep">至</span>
                            <input type="date" class="crm-input-v2 crm-input-date" name="filter_created_to" value="<?php echo htmlspecialchars($_GET['filter_created_to'] ?? '', ENT_QUOTES); ?>" placeholder="结束">
                        </div>
                    </div>
                    <div class="crm-filter-field">
                        <label class="crm-filter-label-v2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <polyline points="10 17 15 12 10 7"/>
                                <line x1="15" y1="12" x2="3" y2="12"/>
                            </svg>
                            最后登录
                        </label>
                        <div class="crm-date-range">
                            <input type="date" class="crm-input-v2 crm-input-date" name="filter_last_login_from" value="<?php echo htmlspecialchars($_GET['filter_last_login_from'] ?? '', ENT_QUOTES); ?>">
                            <span class="crm-date-sep">至</span>
                            <input type="date" class="crm-input-v2 crm-input-date" name="filter_last_login_to" value="<?php echo htmlspecialchars($_GET['filter_last_login_to'] ?? '', ENT_QUOTES); ?>">
                        </div>
                    </div>
                </div>
                <div class="crm-filters-footer">
                    <button type="submit" class="crm-btn-v2 crm-btn-primary-v2 crm-btn-sm">
                        应用筛选
                    </button>
                    <a href="?" class="crm-btn-v2 crm-btn-glass crm-btn-sm">
                        清除筛选
                    </a>
                    <?php if (!empty($_GET)): ?>
                        <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sortBy ?? 'id', ENT_QUOTES); ?>">
                        <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sortOrder ?? 'desc', ENT_QUOTES); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- 统计卡片 -->
    <div class="crm-stats-row">
        <div class="crm-stat-card-v2 crm-stat-purple">
            <div class="crm-stat-icon-wrap">
                <div class="crm-stat-icon-inner">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>
            <div class="crm-stat-info">
                <div class="crm-stat-number"><?php echo number_format($data['total'] ?? 0); ?></div>
                <div class="crm-stat-label">总用户数</div>
            </div>
            <div class="crm-stat-trend crm-trend-up">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
            </div>
        </div>
        <div class="crm-stat-card-v2 crm-stat-green">
            <div class="crm-stat-icon-wrap">
                <div class="crm-stat-icon-inner">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
            </div>
            <div class="crm-stat-info">
                <div class="crm-stat-number"><?php echo number_format($data['activeCount'] ?? 0); ?></div>
                <div class="crm-stat-label">正常用户</div>
            </div>
            <div class="crm-stat-percent">
                <?php 
                $total = $data['total'] ?? 1;
                $activePercent = round(($data['activeCount'] ?? 0) / $total * 100);
                echo $activePercent . '%';
                ?>
            </div>
        </div>
        <div class="crm-stat-card-v2 crm-stat-orange">
            <div class="crm-stat-icon-wrap">
                <div class="crm-stat-icon-inner">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                    </svg>
                </div>
            </div>
            <div class="crm-stat-info">
                <div class="crm-stat-number"><?php echo number_format($data['disabledCount'] ?? 0); ?></div>
                <div class="crm-stat-label">禁用用户</div>
            </div>
        </div>
        <div class="crm-stat-card-v2 crm-stat-blue">
            <div class="crm-stat-icon-wrap">
                <div class="crm-stat-icon-inner">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
            </div>
            <div class="crm-stat-info">
                <div class="crm-stat-number"><?php echo number_format($data['frozenCount'] ?? 0); ?></div>
                <div class="crm-stat-label">冻结用户</div>
            </div>
        </div>
    </div>

    <!-- 批量操作栏 -->
    <div id="batchActions" class="crm-batch-bar" style="display:none;">
        <div class="crm-batch-inner">
            <div class="crm-batch-left">
                <label class="crm-checkbox-wrap">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    <span class="crm-checkbox-custom"></span>
                </label>
                <span class="crm-batch-text">已选择 <strong id="selectedCount">0</strong> 个用户</span>
            </div>
            <div class="crm-batch-right">
                <button type="button" class="crm-btn-v2 crm-btn-success-v2 crm-btn-sm" onclick="batchAction('enable')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    启用
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-warning-v2 crm-btn-sm" onclick="batchAction('disable')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                    </svg>
                    禁用
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-info-v2 crm-btn-sm" onclick="batchAction('freeze')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                    </svg>
                    冻结
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-danger-v2 crm-btn-sm" onclick="batchAction('delete')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    删除
                </button>
                <button type="button" class="crm-btn-v2 crm-btn-glass crm-btn-sm" onclick="clearSelection()">
                    取消
                </button>
            </div>
        </div>
    </div>

    <!-- 用户列表 -->
    <div class="crm-table-card">
        <div class="crm-table-header">
            <div class="crm-table-title">
                <span>用户列表</span>
                <span class="crm-table-count"><?php echo number_format($data['total'] ?? 0); ?> 条记录</span>
            </div>
            <div class="crm-table-tools">
                <div class="crm-view-switch">
                    <button class="crm-view-btn active" data-view="table" title="表格视图">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                    <button class="crm-view-btn" data-view="grid" title="卡片视图">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="crm-table-wrapper-v2">
            <table class="crm-data-table">
                <thead>
                    <tr>
                        <th class="crm-col-checkbox">
                            <label class="crm-checkbox-wrap">
                                <input type="checkbox" id="selectAllHead" onchange="toggleSelectAll(this)">
                                <span class="crm-checkbox-custom"></span>
                            </label>
                        </th>
                        <th class="crm-col-id">
                            <a href="<?php echo get_sort_link('id', $sortBy, $sortOrder); ?>" class="crm-sort-link <?php echo $sortBy === 'id' ? 'is-active' : ''; ?>">
                                ID
                                <span class="crm-sort-icon"><?php echo $sortBy === 'id' ? ($sortOrder === 'asc' ? '↑' : '↓') : '↕'; ?></span>
                            </a>
                        </th>
                        <th class="crm-col-user">
                            <a href="<?php echo get_sort_link('username', $sortBy, $sortOrder); ?>" class="crm-sort-link <?php echo $sortBy === 'username' ? 'is-active' : ''; ?>">
                                用户信息
                                <span class="crm-sort-icon"><?php echo $sortBy === 'username' ? ($sortOrder === 'asc' ? '↑' : '↓') : '↕'; ?></span>
                            </a>
                        </th>
                        <th class="crm-col-email">邮箱</th>
                        <th class="crm-col-coin">星夜币</th>
                        <th class="crm-col-membership">会员等级</th>
                        <th class="crm-col-date">注册时间</th>
                        <th class="crm-col-date">最后登录</th>
                        <th class="crm-col-status">
                            <a href="<?php echo get_sort_link('status', $sortBy, $sortOrder); ?>" class="crm-sort-link <?php echo $sortBy === 'status' ? 'is-active' : ''; ?>">
                                状态
                                <span class="crm-sort-icon"><?php echo $sortBy === 'status' ? ($sortOrder === 'asc' ? '↑' : '↓') : '↕'; ?></span>
                            </a>
                        </th>
                        <th class="crm-col-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['users'])): ?>
                        <?php foreach ($data['users'] as $user): ?>
                            <tr class="crm-data-row" data-user-id="<?php echo $user['id']; ?>">
                                <td class="crm-col-checkbox">
                                    <label class="crm-checkbox-wrap">
                                        <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" onchange="updateBatchActions()">
                                        <span class="crm-checkbox-custom"></span>
                                    </label>
                                </td>
                                <td class="crm-col-id">
                                    <span class="crm-id-badge">#<?php echo $user['id']; ?></span>
                                </td>
                                <td class="crm-col-user">
                                    <a href="javascript:void(0)" onclick="if(window.showUserDetailsModal) { window.showUserDetailsModal(<?php echo $user['id']; ?>); } else { window.location.href='/<?= $adminPrefix ?>/crm/user/<?php echo $user['id']; ?>'; } return false;" class="crm-user-cell">
                                        <div class="crm-user-avatar-v2">
                                            <?php if (!empty($user['avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                                            <?php else: ?>
                                                <span class="crm-avatar-text"><?php echo mb_strtoupper(mb_substr($user['username'], 0, 1)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="crm-user-meta">
                                            <span class="crm-user-name-v2"><?php echo htmlspecialchars($user['username']); ?></span>
                                            <?php $nick = trim($user['nickname'] ?? ''); if ($nick !== '' && $nick !== $user['username']): ?>
                                            <span class="crm-user-nick"><?php echo htmlspecialchars($nick); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <svg class="crm-arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 18 15 12 9 6"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="crm-col-email">
                                    <span class="crm-email-text"><?php echo htmlspecialchars($user['email']); ?></span>
                                </td>
                                <td class="crm-col-coin">
                                    <div class="crm-coin-cell">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 6v12"/>
                                            <path d="M8 10h8"/>
                                            <path d="M8 14h8"/>
                                        </svg>
                                        <span><?php echo number_format($user['coin_balance'] ?? 0); ?></span>
                                    </div>
                                </td>
                                <td class="crm-col-membership">
                                    <?php 
                                    $membership = $user['membership_level_name'] ?? '非会员';
                                    $isMember = !empty($user['membership_level_name']);
                                    ?>
                                    <span class="crm-membership-tag <?php echo $isMember ? 'crm-member-vip' : 'crm-member-none'; ?>">
                                        <?php if ($isMember): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                        <?php endif; ?>
                                        <?php echo $membership; ?>
                                    </span>
                                </td>
                                <td class="crm-col-date">
                                    <span class="crm-date-text"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></span>
                                </td>
                                <td class="crm-col-date">
                                    <span class="crm-date-text <?php echo empty($user['last_login_at']) ? 'crm-never-login' : ''; ?>">
                                        <?php echo $user['last_login_at'] ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : '从未登录'; ?>
                                    </span>
                                </td>
                                <td class="crm-col-status">
                                    <?php 
                                    $status = $user['status'] ?? 'active';
                                    $statusConfig = [
                                        'active' => ['label' => '正常', 'class' => 'crm-status-active-v2', 'icon' => 'check'],
                                        'disabled' => ['label' => '禁用', 'class' => 'crm-status-disabled-v2', 'icon' => 'x'],
                                        'frozen' => ['label' => '冻结', 'class' => 'crm-status-frozen-v2', 'icon' => 'snowflake'],
                                        'deleted' => ['label' => '已删除', 'class' => 'crm-status-deleted-v2', 'icon' => 'trash']
                                    ];
                                    $statusInfo = $statusConfig[$status] ?? ['label' => $status, 'class' => 'crm-status-unknown', 'icon' => 'help'];
                                    ?>
                                    <span class="crm-status-tag <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>
                                <td class="crm-col-actions">
                                    <div class="crm-actions-dropdown-v2">
                                        <button class="crm-actions-trigger" 
                                                onclick="showUserActionModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($status, ENT_QUOTES); ?>')"
                                                title="更多操作">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="1"/>
                                                <circle cx="12" cy="5" r="1"/>
                                                <circle cx="12" cy="19" r="1"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="crm-empty-row-v2">
                            <td colspan="10">
                                <div class="crm-empty-state-v2">
                                    <div class="crm-empty-icon-v2">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <line x1="17" y1="11" x2="23" y2="11"/>
                                        </svg>
                                    </div>
                                    <div class="crm-empty-title-v2">没有找到符合条件的用户</div>
                                    <div class="crm-empty-desc-v2">请尝试调整筛选条件或添加新用户</div>
                                    <button class="crm-btn-v2 crm-btn-primary-v2" onclick="showAddUserModal()">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        添加新用户
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 分页 -->
    <?php if ($data['totalPages'] > 1): ?>
    <div class="crm-pagination-v2">
        <div class="crm-pagination-info">
            显示 <strong><?php echo (($data['page'] - 1) * $data['perPage'] + 1); ?></strong> - <strong><?php echo min($data['page'] * $data['perPage'], $data['total']); ?></strong> 条，共 <strong><?php echo $data['total']; ?></strong> 条
        </div>
        <div class="crm-pagination-controls">
            <a href="?page=<?php echo $data['page'] - 1; ?>&search=<?php echo urlencode($searchTerm ?? ''); ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?>" 
               class="crm-page-btn <?php echo ($data['page'] <= 1) ? 'crm-page-disabled' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>
            
            <?php
            $start = max(1, $data['page'] - 2);
            $end = min($data['totalPages'], $data['page'] + 2);
            
            if ($start > 1) {
                echo '<a href="?page=1&search=' . urlencode($searchTerm ?? '') . '&sort_by=' . $sortBy . '&sort_order=' . $sortOrder . '" class="crm-page-btn">1</a>';
                if ($start > 2) {
                    echo '<span class="crm-page-ellipsis">...</span>';
                }
            }
            
            for ($i = $start; $i <= $end; $i++) {
                $activeClass = ($i == $data['page']) ? 'crm-page-active' : '';
                echo '<a href="?page=' . $i . '&search=' . urlencode($searchTerm ?? '') . '&sort_by=' . $sortBy . '&sort_order=' . $sortOrder . '" class="crm-page-btn ' . $activeClass . '">' . $i . '</a>';
            }
            
            if ($end < $data['totalPages']) {
                if ($end < $data['totalPages'] - 1) {
                    echo '<span class="crm-page-ellipsis">...</span>';
                }
                echo '<a href="?page=' . $data['totalPages'] . '&search=' . urlencode($searchTerm ?? '') . '&sort_by=' . $sortBy . '&sort_order=' . $sortOrder . '" class="crm-page-btn">' . $data['totalPages'] . '</a>';
            }
            ?>
            
            <a href="?page=<?php echo $data['page'] + 1; ?>&search=<?php echo urlencode($searchTerm ?? ''); ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?>" 
               class="crm-page-btn <?php echo ($data['page'] >= $data['totalPages']) ? 'crm-page-disabled' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- CRM 用户管理交互脚本 -->
<script>
// 设置全局变量，确保在脚本加载前可用
window.adminPrefix = '<?= $adminPrefix ?>';
if (!window.ADMIN_PREFIX) {
    window.ADMIN_PREFIX = window.adminPrefix;
}
</script>
<!-- 注意：crm-users.js 已在 layout.php 中加载，这里不需要重复加载 -->
<script>
// 等待所有脚本加载完成后验证
window.addEventListener('load', function() {
    console.log('Page loaded, checking functions...');
    console.log('showAddUserModal:', typeof window.showAddUserModal);
    console.log('showUserDetailsModal:', typeof window.showUserDetailsModal);
    console.log('showEditUserModal:', typeof window.showEditUserModal);
    console.log('Modal:', typeof window.Modal);
    
    // 如果函数未定义，提供降级处理
    if (typeof window.showAddUserModal !== 'function') {
        console.warn('showAddUserModal not found, creating fallback');
        window.showAddUserModal = function() {
            const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
            window.location.href = '/' + adminPrefix + '/crm/user/add';
        };
    }
    
    if (typeof window.showUserDetailsModal !== 'function') {
        console.warn('showUserDetailsModal not found, creating fallback');
        window.showUserDetailsModal = function(userId) {
            const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
            window.location.href = '/' + adminPrefix + '/crm/user/' + userId;
        };
    }
    
    if (typeof window.showEditUserModal !== 'function') {
        console.warn('showEditUserModal not found, creating fallback');
        window.showEditUserModal = function(userId) {
            const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
            window.location.href = '/' + adminPrefix + '/crm/user/' + userId + '/edit';
        };
    }
});
</script>