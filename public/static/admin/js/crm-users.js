/**
 * CRM 用户管理界面交互脚本
 * 提供用户管理页面的交互功能
 */

(function() {
    'use strict';

    // ===== 全局状态 =====
    const state = {
        selectedUsers: new Set(),
        filtersVisible: false,
        currentSort: {
            field: 'id',
            order: 'desc'
        }
    };

    // ===== 管理路径 =====
    const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';

    // ===== 初始化时修复下拉菜单遮挡问题 =====
    function fixDropdownZIndex() {
        // 为表格容器添加相对定位，确保下拉菜单可以正确显示
        const tableWrapper = document.querySelector('.crm-table-wrapper');
        if (tableWrapper) {
            tableWrapper.style.position = 'relative';
            tableWrapper.style.zIndex = '1';
        }

        // 确保下拉菜单有足够高的z-index
        const dropdowns = document.querySelectorAll('.crm-actions-panel');
        dropdowns.forEach(menu => {
            menu.style.zIndex = '10000';
        });
    }

    // ===== DOM 元素引用 =====
    const elements = {
        advancedFilters: null,
        filterToggleText: null,
        batchActions: null,
        selectedCount: null,
        selectAllCheckbox: null,
        filterForm: null
    };

    // ===== 初始化 =====
    function init() {
        // 等待 DOM 加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
    }

    function setup() {
        // 获取 DOM 元素
        cacheElements();

        // 绑定事件
        bindEvents();

        // 初始化状态
        initializeState();

        // 修复下拉菜单遮挡问题
        fixDropdownZIndex();

        // 恢复视图偏好
        restoreViewPreference();

        // 添加键盘快捷键
        setupKeyboardShortcuts();

        // 添加动画效果
        enhanceAnimations();
    }

    function cacheElements() {
        elements.advancedFilters = document.getElementById('advancedFilters');
        elements.filterToggleText = document.getElementById('filterToggleText');
        elements.batchActions = document.getElementById('batchActions');
        elements.selectedCount = document.getElementById('selectedCount');
        elements.selectAllCheckbox = document.getElementById('selectAll');
        elements.filterForm = document.getElementById('filterForm');
    }

    function bindEvents() {
        // 全选复选框
        if (elements.selectAllCheckbox) {
            elements.selectAllCheckbox.addEventListener('change', handleSelectAll);
        }

        // 用户复选框
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', handleUserCheckboxChange);
        });

        // 点击外部关闭下拉菜单
        document.addEventListener('click', handleDocumentClick);

        // 表单提交
        if (elements.filterForm) {
            elements.filterForm.addEventListener('submit', handleFilterSubmit);
        }

        // 表格行点击
        document.querySelectorAll('.crm-table-row').forEach(row => {
            row.addEventListener('dblclick', handleRowDoubleClick);
        });

        // 下拉菜单中的操作按钮（弹窗确认）
        document.querySelectorAll('[data-user-action]').forEach(btn => {
            btn.addEventListener('click', handleUserActionClick);
        });

        // 视图切换按钮
        document.querySelectorAll('.crm-view-btn').forEach(btn => {
            btn.addEventListener('click', handleViewSwitch);
        });

        // 添加滚动效果
        setupScrollEffects();
    }

    // ===== 用户操作弹窗确认 =====
    function handleUserActionClick(event) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const action = button.getAttribute('data-user-action');
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        const userStatus = button.getAttribute('data-user-status');

        const user = {
            id: userId,
            username: userName,
            status: userStatus
        };

        // 关闭下拉菜单
        const menu = button.closest('.crm-actions-dropdown-v2')?.querySelector('.crm-actions-panel');
        if (menu) {
            menu.style.display = 'none';
        }

        // 显示确认弹窗
        showActionConfirmModal(action, user);
    }

    async function showActionConfirmModal(action, user) {
        const actionConfig = {
            disable: {
                title: user.status === 'active' ? '禁用用户' : '启用用户',
                message: `确定要${user.status === 'active' ? '禁用' : '启用'}用户 "${user.username}" 吗？`,
                type: 'warning',
                url: `/${adminPrefix}/crm/user/${user.id}/toggle`
            },
            freeze: {
                title: '冻结用户',
                message: `确定要冻结用户 "${user.username}" 吗？`,
                type: 'warning',
                url: `/${adminPrefix}/crm/user/${user.id}/freeze`
            },
            unfreeze: {
                title: '解冻用户',
                message: `确定要解冻用户 "${user.username}" 吗？`,
                type: 'info',
                url: `/${adminPrefix}/crm/user/${user.id}/unfreeze`
            },
            delete: {
                title: '删除用户',
                message: `确定要删除用户 "${user.username}" 吗？\n\n此操作不可恢复！`,
                type: 'danger',
                url: `/${adminPrefix}/crm/user/${user.id}/delete`
            },
            restore: {
                title: '恢复用户',
                message: `确定要恢复用户 "${user.username}" 吗？`,
                type: 'info',
                url: `/${adminPrefix}/crm/user/${user.id}/restore`
            }
        };

        const config = actionConfig[action];
        if (!config) return;

        // 等待 Modal 加载完成
        if (!window.Modal || typeof window.Modal.confirm !== 'function') {
            console.log('Waiting for Modal to be ready...');
            await new Promise(resolve => {
                let attempts = 0;
                const maxAttempts = 30; // 最多等待3秒
                const checkModal = setInterval(() => {
                    attempts++;
                    if ((window.Modal && typeof window.Modal.confirm === 'function') || attempts > maxAttempts) {
                        clearInterval(checkModal);
                        resolve();
                    }
                }, 100);
            });
        }

        // 显示确认弹窗
        if (window.Modal && typeof window.Modal.confirm === 'function') {
            try {
                const confirmed = await window.Modal.confirm(config.message, config.title, config.type);
                if (confirmed) {
                    window.location.href = config.url;
                }
            } catch (error) {
                console.error('Modal.confirm error:', error);
                // 降级处理：使用原生confirm
                if (confirm(config.message)) {
                    window.location.href = config.url;
                }
            }
        } else {
            // 降级处理：使用原生confirm
            console.warn('Modal.confirm not available, using native confirm');
            if (confirm(config.message)) {
                window.location.href = config.url;
            }
        }
    }

    function initializeState() {
        // 检查 URL 参数以恢复筛选状态
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString().length > 0 && !urlParams.has('page')) {
            state.filtersVisible = true;
            updateFiltersVisibility();
        }

        // 初始化已选择的用户
        document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
            state.selectedUsers.add(checkbox.value);
        });
        updateBatchActions();
    }

    // ===== 筛选面板 =====
    window.toggleAdvancedFilters = function() {
        state.filtersVisible = !state.filtersVisible;
        updateFiltersVisibility();
    };

    function updateFiltersVisibility() {
        if (!elements.advancedFilters || !elements.filterToggleText) return;

        if (state.filtersVisible) {
            elements.advancedFilters.style.display = 'block';
            elements.filterToggleText.textContent = '隐藏筛选';
            
            // 添加动画类
            elements.advancedFilters.classList.add('crm-filters-panel--visible');
            
            // 聚焦到第一个输入框
            const firstInput = elements.advancedFilters.querySelector('input, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        } else {
            elements.advancedFilters.style.display = 'none';
            elements.filterToggleText.textContent = '高级筛选';
            elements.advancedFilters.classList.remove('crm-filters-panel--visible');
        }
    }

    function handleFilterSubmit(event) {
        // 可以在这里添加表单验证
        const formData = new FormData(elements.filterForm);
        
        // 检查日期范围是否有效
        const createdFrom = formData.get('filter_created_from');
        const createdTo = formData.get('filter_created_to');
        
        if (createdFrom && createdTo && createdFrom > createdTo) {
            event.preventDefault();
            showNotification('注册时间范围无效', 'error');
            return false;
        }

        const loginFrom = formData.get('filter_last_login_from');
        const loginTo = formData.get('filter_last_login_to');
        
        if (loginFrom && loginTo && loginFrom > loginTo) {
            event.preventDefault();
            showNotification('最后登录时间范围无效', 'error');
            return false;
        }

        return true;
    }

    // ===== 批量操作 =====
    function handleSelectAll(event) {
        const isChecked = event.target.checked;
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
            if (isChecked) {
                state.selectedUsers.add(checkbox.value);
            } else {
                state.selectedUsers.delete(checkbox.value);
            }
        });
        updateBatchActions();
    }

    function handleUserCheckboxChange(event) {
        const checkbox = event.target;
        if (checkbox.checked) {
            state.selectedUsers.add(checkbox.value);
        } else {
            state.selectedUsers.delete(checkbox.value);
            if (elements.selectAllCheckbox) {
                elements.selectAllCheckbox.checked = false;
            }
        }
        updateBatchActions();
    }

    function updateBatchActions() {
        // 重新收集选中的用户
        state.selectedUsers.clear();
        document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
            state.selectedUsers.add(checkbox.value);
        });

        if (!elements.batchActions || !elements.selectedCount) return;

        const count = state.selectedUsers.size;
        if (count > 0) {
            elements.batchActions.style.display = 'block';
            elements.selectedCount.textContent = count;
            
            // 添加动画
            elements.batchActions.classList.add('crm-batch-actions--visible');
        } else {
            elements.batchActions.style.display = 'none';
            elements.batchActions.classList.remove('crm-batch-actions--visible');
        }
    }

    // 暴露为全局函数
    window.updateBatchActions = updateBatchActions;

    window.batchAction = function(action) {
        if (state.selectedUsers.size === 0) {
            showNotification('请先选择用户', 'warning');
            return;
        }

        const actionMessages = {
            'enable': '启用',
            'disable': '禁用',
            'freeze': '冻结',
            'delete': '删除'
        };

        const message = `确定要对 ${state.selectedUsers.size} 个用户执行${actionMessages[action]}操作吗？`;
        
        if (confirm(message)) {
            // 执行批量操作
            performBatchAction(action);
        }
    };

    function performBatchAction(action) {
        const userIds = Array.from(state.selectedUsers);
        
        // 这里可以发送 AJAX 请求
        console.log('批量操作:', action, userIds);
        
        // 模拟操作完成
        showNotification(`批量${action}操作已执行`, 'success');
        
        // 清除选择
        clearSelection();
    }

    window.clearSelection = function() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        state.selectedUsers.clear();
        if (elements.selectAllCheckbox) {
            elements.selectAllCheckbox.checked = false;
        }
        updateBatchActions();
    };

    // ===== 用户操作弹窗 =====
    window.showUserActionModal = async function(userId, userName, userEmail, userStatus) {
        // 等待 Modal 和 UserActionModal 加载
        if (!window.Modal || !window.UserActionModal) {
            // 如果还没加载，等待一下
            await new Promise(resolve => {
                let attempts = 0;
                const maxAttempts = 50; // 增加等待时间到5秒
                const checkModal = setInterval(() => {
                    attempts++;
                    if ((window.Modal && window.UserActionModal) || attempts > maxAttempts) {
                        clearInterval(checkModal);
                        resolve();
                    }
                }, 100);
            });
        }

        if (!window.Modal) {
            console.error('window.Modal is not available. Please check if modal.js is loaded.');
            alert('弹窗组件未加载，请刷新页面重试');
            return;
        }

        if (!window.UserActionModal) {
            console.error('UserActionModal is not available. Please check if modal.js is loaded.');
            alert('弹窗组件未加载，请刷新页面重试');
            return;
        }

        const user = {
            id: userId,
            username: userName,
            email: userEmail,
            status: userStatus
        };

        try {
            // 显示操作选择弹窗
            const action = await window.UserActionModal.show(user, userStatus);
            
            if (!action) {
                return; // 用户取消了操作
            }

            // 执行选中的操作
            const confirmed = await window.UserActionModal.executeAction(userId, action, user);
            
            if (confirmed && action !== 'view' && action !== 'edit') {
                // 构建操作URL
                const currentAdminPrefix = window.ADMIN_PREFIX || window.adminPrefix || adminPrefix;
                const actionUrls = {
                    enable: `/${currentAdminPrefix}/crm/user/${userId}/toggle`,
                    disable: `/${currentAdminPrefix}/crm/user/${userId}/toggle`,
                    freeze: `/${currentAdminPrefix}/crm/user/${userId}/freeze`,
                    unfreeze: `/${currentAdminPrefix}/crm/user/${userId}/unfreeze`,
                    delete: `/${currentAdminPrefix}/crm/user/${userId}/delete`,
                    restore: `/${currentAdminPrefix}/crm/user/${userId}/restore`
                };

                const url = actionUrls[action];
                if (url) {
                    window.location.href = url;
                }
            }
        } catch (error) {
            console.error('Error showing user action modal:', error);
            alert('操作失败：' + error.message);
        }
    };

    // ===== 视图切换 =====
    function handleViewSwitch(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const button = event.currentTarget;
        const viewType = button.getAttribute('data-view');
        
        if (!viewType) return;

        // 更新按钮状态
        document.querySelectorAll('.crm-view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');

        // 切换视图
        const tableWrapper = document.querySelector('.crm-table-wrapper-v2');
        const tableCard = document.querySelector('.crm-table-card');
        const dataTable = document.querySelector('.crm-data-table');
        
        if (!tableWrapper || !tableCard || !dataTable) {
            console.warn('Table wrapper, card or table not found');
            return;
        }

        if (viewType === 'grid') {
            // 切换到卡片视图
            tableCard.classList.add('crm-view-grid');
            tableWrapper.classList.add('crm-view-grid');
            
            // 生成卡片视图
            generateCardView();
            
            // 保存视图偏好
            localStorage.setItem('crm-users-view', 'grid');
        } else {
            // 切换到表格视图
            tableCard.classList.remove('crm-view-grid');
            tableWrapper.classList.remove('crm-view-grid');
            
            // 移除卡片视图
            const cardsContainer = tableWrapper.querySelector('.crm-cards-container');
            if (cardsContainer) {
                cardsContainer.remove();
            }
            
            // 显示表格
            dataTable.style.display = '';
            
            // 保存视图偏好
            localStorage.setItem('crm-users-view', 'table');
        }
    }

    // ===== 生成卡片视图 =====
    function generateCardView() {
        const tableWrapper = document.querySelector('.crm-table-wrapper-v2');
        const dataTable = document.querySelector('.crm-data-table');
        const tbody = dataTable.querySelector('tbody');
        
        if (!tableWrapper || !tbody) return;

        // 检查是否已经存在卡片容器
        let cardsContainer = tableWrapper.querySelector('.crm-cards-container');
        if (cardsContainer) {
            return; // 已经生成过了
        }

        // 隐藏表格
        dataTable.style.display = 'none';

        // 创建卡片容器
        cardsContainer = document.createElement('div');
        cardsContainer.className = 'crm-cards-container';
        cardsContainer.style.display = 'grid';
        cardsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        cardsContainer.style.gap = 'var(--crm-space-lg)';
        cardsContainer.style.padding = 'var(--crm-space-lg)';

        // 获取所有表格行
        const rows = tbody.querySelectorAll('.crm-data-row');
        
        if (rows.length === 0) {
            // 空状态
            cardsContainer.innerHTML = `
                <div class="crm-empty-state-v2" style="grid-column: 1 / -1;">
                    <div class="crm-empty-icon-v2">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <div class="crm-empty-title-v2">暂无用户数据</div>
                    <div class="crm-empty-desc-v2">请尝试调整筛选条件</div>
                </div>
            `;
            tableWrapper.appendChild(cardsContainer);
            return;
        }

        // 为每一行生成卡片
        rows.forEach(row => {
            const card = createUserCard(row);
            if (card) {
                cardsContainer.appendChild(card);
            }
        });

        tableWrapper.appendChild(cardsContainer);
    }

    // ===== 创建用户卡片 =====
    function createUserCard(row) {
        try {
            // 提取数据
            const userId = row.getAttribute('data-user-id') || row.querySelector('.user-checkbox')?.value;
            const userCell = row.querySelector('.crm-user-cell');
            const userName = userCell?.querySelector('.crm-user-name-v2')?.textContent?.trim() || '未知用户';
            const userNick = userCell?.querySelector('.crm-user-nick')?.textContent?.trim() || '';
            const avatar = userCell?.querySelector('.crm-user-avatar-v2 img')?.src || '';
            const avatarText = userCell?.querySelector('.crm-avatar-text')?.textContent?.trim() || '';
            
            const email = row.querySelector('.crm-email-text')?.textContent?.trim() || '';
            const coin = row.querySelector('.crm-coin-cell span')?.textContent?.trim() || '0';
            const membership = row.querySelector('.crm-membership-tag')?.textContent?.trim() || '非会员';
            const createdDate = row.querySelectorAll('.crm-col-date .crm-date-text')[0]?.textContent?.trim() || '';
            const lastLogin = row.querySelectorAll('.crm-col-date .crm-date-text')[1]?.textContent?.trim() || '';
            const statusTag = row.querySelector('.crm-status-tag');
            const statusText = statusTag?.textContent?.trim() || '';
            const statusClass = Array.from(statusTag?.classList || []).find(cls => cls.startsWith('crm-status-')) || '';
            
            const checkbox = row.querySelector('.user-checkbox');
            const isChecked = checkbox?.checked || false;

            // 创建卡片
            const card = document.createElement('div');
            card.className = 'crm-user-card';
            card.setAttribute('data-user-id', userId);

            const currentAdminPrefix = window.ADMIN_PREFIX || window.adminPrefix || adminPrefix;

            // 转义用户状态用于 onclick
            const safeUserName = escapeHtml(userName).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeEmail = escapeHtml(email).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeStatus = statusClass.replace('crm-status-', '').replace('-v2', '');

            card.innerHTML = `
                <div class="crm-user-card-header">
                    <label class="crm-checkbox-wrap">
                        <input type="checkbox" class="user-checkbox" value="${userId}" ${isChecked ? 'checked' : ''} onchange="if(window.updateBatchActions) window.updateBatchActions()">
                        <span class="crm-checkbox-custom"></span>
                    </label>
                    <a href="/${currentAdminPrefix}/crm/user/${userId}" class="crm-user-cell" style="flex: 1; text-decoration: none; color: inherit;">
                        <div class="crm-user-avatar-v2">
                            ${avatar ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(userName)}">` : `<span class="crm-avatar-text">${escapeHtml(avatarText || userName.charAt(0).toUpperCase())}</span>`}
                        </div>
                        <div class="crm-user-meta">
                            <span class="crm-user-name-v2">${escapeHtml(userName)}</span>
                            ${userNick ? `<span class="crm-user-nick">${escapeHtml(userNick)}</span>` : ''}
                        </div>
                    </a>
                    <button class="crm-actions-trigger" 
                            onclick="if(window.showUserActionModal) window.showUserActionModal(${userId}, '${safeUserName}', '${safeEmail}', '${safeStatus}'); return false;"
                            title="更多操作"
                            type="button">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"/>
                            <circle cx="12" cy="5" r="1"/>
                            <circle cx="12" cy="19" r="1"/>
                        </svg>
                    </button>
                </div>
                <div class="crm-user-card-body">
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">邮箱</span>
                        <span class="crm-user-card-value">${escapeHtml(email) || '<span style="color: var(--text-muted); font-style: italic;">未设置</span>'}</span>
                    </div>
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">星夜币</span>
                        <span class="crm-user-card-value" style="color: #fbbf24; font-family: 'SF Mono', 'Monaco', monospace;">${escapeHtml(coin)}</span>
                    </div>
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">会员等级</span>
                        <span class="crm-user-card-value">${escapeHtml(membership)}</span>
                    </div>
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">注册时间</span>
                        <span class="crm-user-card-value">${escapeHtml(createdDate)}</span>
                    </div>
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">最后登录</span>
                        <span class="crm-user-card-value">${escapeHtml(lastLogin) || '<span style="color: var(--text-muted); font-style: italic;">从未登录</span>'}</span>
                    </div>
                    <div class="crm-user-card-field">
                        <span class="crm-user-card-label">状态</span>
                        <span class="crm-user-card-value"><span class="crm-status-tag ${statusClass}">${escapeHtml(statusText)}</span></span>
                    </div>
                </div>
            `;

            return card;
        } catch (error) {
            console.error('Error creating user card:', error);
            return null;
        }
    }

    // ===== HTML转义辅助函数 =====
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ===== 恢复视图偏好 =====
    function restoreViewPreference() {
        const savedView = localStorage.getItem('crm-users-view') || 'table';
        const viewButton = document.querySelector(`.crm-view-btn[data-view="${savedView}"]`);
        const tableWrapper = document.querySelector('.crm-table-wrapper-v2');
        const tableCard = document.querySelector('.crm-table-card');
        
        if (viewButton && tableWrapper && tableCard) {
            // 更新按钮状态
            document.querySelectorAll('.crm-view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            viewButton.classList.add('active');
            
            // 应用视图样式
            if (savedView === 'grid') {
                tableCard.classList.add('crm-view-grid');
                tableWrapper.classList.add('crm-view-grid');
                // 延迟生成卡片，确保DOM已完全加载
                setTimeout(() => {
                    generateCardView();
                }, 100);
            } else {
                tableCard.classList.remove('crm-view-grid');
                tableWrapper.classList.remove('crm-view-grid');
                const cardsContainer = tableWrapper.querySelector('.crm-cards-container');
                if (cardsContainer) {
                    cardsContainer.remove();
                }
                const dataTable = document.querySelector('.crm-data-table');
                if (dataTable) {
                    dataTable.style.display = '';
                }
            }
        }
    }

    // ===== 操作菜单（保留以兼容旧代码） =====
    window.toggleActions = function(userId) {
        // 兼容旧代码，但建议使用 showUserActionModal
        console.warn('toggleActions is deprecated, use showUserActionModal instead');
    };

    function handleDocumentClick(event) {
        if (!event.target.closest('.crm-actions-dropdown-v2')) {
            document.querySelectorAll('.crm-actions-panel').forEach(menu => {
                menu.style.display = 'none';
                menu.classList.remove('crm-actions-panel--visible');
            });
        }
    }

    // ===== 用户操作 =====
    window.deleteUser = function(userId) {
        if (confirm('确定要删除该用户吗？此操作不可恢复！')) {
            // 这里可以发送 AJAX 请求
            console.log('删除用户:', userId);
            
            // 模拟删除
            showNotification('用户已删除', 'success');
            
            // 移除表格行
            const row = document.querySelector(`tr:has(.user-checkbox[value="${userId}"])`);
            if (row) {
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        }
    };

    // ===== 导出功能 =====
    window.exportUsers = function() {
        showNotification('导出功能开发中...', 'info');
        
        // 这里可以实现导出逻辑
        // 例如：导出当前筛选结果为 CSV 或 Excel
    };

    // ===== 添加新用户 =====
    window.showAddUserModal = async function() {
        console.log('showAddUserModal called');
        const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
        
        try {
            // 通过 AJAX 加载添加用户表单
            const response = await fetch(`/${adminPrefix}/crm/user/add`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // 先读取响应文本（只能读取一次）
            const responseText = await response.text();
            
            if (!response.ok) {
                // 尝试解析为 JSON 错误信息
                let errorMessage = '加载失败: ' + response.status;
                try {
                    const errorData = JSON.parse(responseText);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                    if (errorData.error && window.console) {
                        console.error('Server error details:', errorData.error);
                    }
                } catch (e) {
                    // 如果不是 JSON，使用原始文本
                    if (responseText) {
                        console.error('Server error response:', responseText);
                        errorMessage += '\n' + responseText.substring(0, 200);
                    }
                }
                throw new Error(errorMessage);
            }
            
            const html = responseText;
            console.log('Form HTML loaded, length:', html.length);
            
            // 检查返回的内容是否是错误信息（JSON格式）
            if (html.trim().startsWith('{') && html.includes('"success"')) {
                try {
                    const errorData = JSON.parse(html);
                    if (!errorData.success) {
                        throw new Error(errorData.message || '加载失败');
                    }
                } catch (e) {
                    // 忽略 JSON 解析错误，继续处理
                }
            }
            
            // 检查 Modal 是否可用，如果不可用则等待
            if (!window.Modal) {
                console.warn('Modal not ready, waiting...');
                await new Promise(resolve => setTimeout(resolve, 500));
                if (!window.Modal) {
                    console.error('Modal still not available after wait');
                    window.location.href = `/${adminPrefix}/crm/user/add`;
                    return;
                }
            }
            
            // 使用 Modal 显示弹窗
            if (window.Modal && window.Modal.create) {
                const modal = window.Modal.create({
                    type: 'info',
                    title: '添加新用户',
                    size: 'xl',
                    message: '',
                    html: html,
                    buttons: []
                });
                
                // 等待弹窗渲染完成
                setTimeout(() => {
                    // 绑定表单提交事件
                    const form = modal.element.querySelector('form');
                if (form) {
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(form);
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn ? submitBtn.innerHTML : '';
                        
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '创建中...';
                        }
                        
                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                modal.close();
                                if (window.CRMUsers && window.CRMUsers.showNotification) {
                                    window.CRMUsers.showNotification(result.message || '用户创建成功', 'success');
                                }
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                alert(result.message || '创建失败');
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalText;
                                }
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('创建失败，请重试');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        }
                    });
                } else {
                    console.warn('Form not found in modal');
                }
                }, 200);
            } else {
                console.error('Modal.create is not available');
                // 降级处理：跳转到页面
                window.location.href = `/${adminPrefix}/crm/user/add`;
            }
        } catch (error) {
            console.error('Error loading add user form:', error);
            alert('加载表单失败: ' + error.message);
            // 降级处理：跳转到页面
            window.location.href = `/${adminPrefix}/crm/user/add`;
        }
    };
    
    // ===== 显示用户详情弹窗 =====
    window.showUserDetailsModal = async function(userId) {
        console.log('showUserDetailsModal called for user:', userId);
        const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
        
        try {
            const response = await fetch(`/${adminPrefix}/crm/user/${userId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // 先读取响应文本（只能读取一次）
            const responseText = await response.text();
            
            if (!response.ok) {
                // 尝试解析为 JSON 错误信息
                let errorMessage = '加载失败: ' + response.status;
                try {
                    const errorData = JSON.parse(responseText);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // 如果不是 JSON，使用原始文本
                    if (responseText) {
                        console.error('Server error response:', responseText);
                        errorMessage += '\n' + responseText.substring(0, 200);
                    }
                }
                throw new Error(errorMessage);
            }
            
            const html = responseText;
            console.log('User details HTML loaded, length:', html.length);
            
            // 检查 Modal 是否可用
            if (!window.Modal) {
                console.error('Modal is not available');
                await new Promise(resolve => setTimeout(resolve, 500));
                if (!window.Modal) {
                    window.location.href = `/${adminPrefix}/crm/user/${userId}`;
                    return;
                }
            }
            
            if (window.Modal && window.Modal.create) {
                window.Modal.create({
                    type: 'info',
                    title: '用户详情',
                    size: 'xl',
                    message: '',
                    html: html,
                    buttons: []
                });
            } else {
                console.error('Modal.create is not available');
                window.location.href = `/${adminPrefix}/crm/user/${userId}`;
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            alert('加载用户详情失败: ' + error.message);
            window.location.href = `/${adminPrefix}/crm/user/${userId}`;
        }
    };
    
    // ===== 显示编辑用户弹窗 =====
    window.showEditUserModal = async function(userId) {
        const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
        
        try {
            const response = await fetch(`/${adminPrefix}/crm/user/${userId}/edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // 先读取响应文本（只能读取一次）
            const responseText = await response.text();
            
            if (!response.ok) {
                // 尝试解析为 JSON 错误信息
                let errorMessage = '加载失败: ' + response.status;
                try {
                    const errorData = JSON.parse(responseText);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // 如果不是 JSON，使用原始文本
                    if (responseText) {
                        console.error('Server error response:', responseText);
                        errorMessage += '\n' + responseText.substring(0, 200);
                    }
                }
                throw new Error(errorMessage);
            }
            
            const html = responseText;
            console.log('Edit form HTML loaded, length:', html.length);
            
            // 检查 Modal 是否可用
            if (!window.Modal) {
                console.warn('Modal not ready, waiting...');
                await new Promise(resolve => setTimeout(resolve, 500));
                if (!window.Modal) {
                    console.error('Modal still not available after wait');
                    window.location.href = `/${adminPrefix}/crm/user/${userId}/edit`;
                    return;
                }
            }
            
            if (window.Modal && window.Modal.create) {
                const modal = window.Modal.create({
                    type: 'info',
                    title: '编辑用户',
                    size: 'xl',
                    message: '',
                    html: html,
                    buttons: []
                });
                
                console.log('Modal created:', modal);
                
                // 等待弹窗渲染完成
                setTimeout(() => {
                    // 绑定表单提交事件
                    const form = modal.element.querySelector('form');
                    if (form) {
                        console.log('Form found, binding submit handler');
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(form);
                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn ? submitBtn.innerHTML : '';
                        
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '保存中...';
                        }
                        
                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                modal.close();
                                if (window.CRMUsers && window.CRMUsers.showNotification) {
                                    window.CRMUsers.showNotification(result.message || '用户更新成功', 'success');
                                }
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                alert(result.message || '更新失败');
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalText;
                                }
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('更新失败，请重试');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        }
                    });
                } else {
                    console.warn('Form not found in modal');
                }
                }, 200);
            } else {
                console.error('Modal.create is not available');
                window.location.href = `/${adminPrefix}/crm/user/${userId}/edit`;
            }
        } catch (error) {
            console.error('Error loading edit user form:', error);
            alert('加载编辑表单失败: ' + error.message);
            window.location.href = `/${adminPrefix}/crm/user/${userId}/edit`;
        }
    };

    // ===== 表格交互 =====
    function handleRowDoubleClick(event) {
        const row = event.currentTarget;
        const userId = row.querySelector('.user-checkbox')?.value;
        
        if (userId) {
            // 双击跳转到用户详情页
            const adminPrefix = document.querySelector('.sidebar-brand')?.getAttribute('href')?.split('/')[1] || 'admin';
            window.location.href = `/${adminPrefix}/crm/user/${userId}`;
        }
    }

    function setupScrollEffects() {
        const tableWrapper = document.querySelector('.crm-table-wrapper');
        if (!tableWrapper) return;

        let scrollTimeout;
        tableWrapper.addEventListener('scroll', function() {
            tableWrapper.classList.add('crm-table-wrapper--scrolling');
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                tableWrapper.classList.remove('crm-table-wrapper--scrolling');
            }, 150);
        });
    }

    // ===== 键盘快捷键 =====
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(event) {
            // Ctrl/Cmd + F: 聚焦搜索框
            if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
                event.preventDefault();
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    if (!state.filtersVisible) {
                        toggleAdvancedFilters();
                    }
                    searchInput.focus();
                }
            }

            // Ctrl/Cmd + A: 全选（仅在筛选面板未聚焦时）
            if ((event.ctrlKey || event.metaKey) && event.key === 'a') {
                const activeElement = document.activeElement;
                if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                    event.preventDefault();
                    if (elements.selectAllCheckbox) {
                        elements.selectAllCheckbox.checked = true;
                        handleSelectAll({ target: elements.selectAllCheckbox });
                    }
                }
            }

            // Escape: 关闭筛选面板或清除选择
            if (event.key === 'Escape') {
                if (state.filtersVisible) {
                    toggleAdvancedFilters();
                } else if (state.selectedUsers.size > 0) {
                    clearSelection();
                }
            }

            // Delete: 删除选中的用户
            if (event.key === 'Delete' && state.selectedUsers.size > 0) {
                event.preventDefault();
                batchAction('delete');
            }
        });
    }

    // ===== 动画增强 =====
    function enhanceAnimations() {
        // 为统计卡片添加交错动画
        const statCards = document.querySelectorAll('.crm-stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.05}s`;
            card.classList.add('crm-stat-card--animate');
        });

        // 为表格行添加交错动画
        const tableRows = document.querySelectorAll('.crm-table-row');
        tableRows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.02}s`;
        });
    }

    // ===== 通知系统 =====
    function showNotification(message, type = 'info') {
        // 创建通知元素
        const notification = document.createElement('div');
        notification.className = `crm-notification crm-notification--${type}`;
        notification.textContent = message;
        
        // 添加样式
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            background: 'var(--bg-sidebar)',
            border: '1px solid var(--border-color)',
            borderRadius: 'var(--radius-md)',
            boxShadow: 'var(--shadow-lg)',
            zIndex: '9999',
            animation: 'slideInRight 0.3s ease',
            backdropFilter: 'blur(20px)'
        });

        // 根据类型设置颜色
        const colors = {
            success: 'var(--success-color)',
            error: 'var(--danger-color)',
            warning: 'var(--warning-color)',
            info: 'var(--info-color)'
        };
        notification.style.borderLeftColor = colors[type] || colors.info;

        document.body.appendChild(notification);

        // 自动移除
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // ===== 工具函数 =====
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ===== 暴露全局函数 =====
    window.CRMUsers = {
        toggleAdvancedFilters,
        batchAction,
        clearSelection,
        toggleActions,
        deleteUser,
        exportUsers,
        showNotification
    };

    // 初始化
    init();

})();

// ===== 添加动画样式 =====
if (!document.getElementById('crm-users-styles')) {
    const crmUsersStyle = document.createElement('style');
    crmUsersStyle.id = 'crm-users-styles';
    crmUsersStyle.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

    .crm-stat-card--animate {
        animation: fadeInUp 0.5s ease both;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .crm-table-wrapper--scrolling {
        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .crm-filters-panel--visible {
        animation: slideDown 0.3s ease;
    }

    .crm-batch-actions--visible {
        animation: slideDown 0.3s ease;
    }

    .crm-actions-menu--visible {
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    `;
    document.head.appendChild(crmUsersStyle);
}
