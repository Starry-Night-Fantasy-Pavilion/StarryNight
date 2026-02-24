/**
 * 弹窗组件
 * 用于用户管理操作确认弹窗
 */

(function() {
    'use strict';

    // ===== 弹窗管理器 =====
    const ModalManager = {
        activeModal: null,
        zIndex: 9998,
        focusableElements: 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',

        /**
         * 创建并显示弹窗
         * @param {Object} options - 弹窗配置
         * @returns {Modal} 弹窗实例
         */
        create(options) {
            const modal = new Modal(options);
            modal.show();
            return modal;
        },

        /**
         * 确认弹窗
         * @param {string} message - 确认消息
         * @param {string} title - 弹窗标题
         * @param {string} type - 弹窗类型 (info, success, warning, danger)
         * @returns {Promise<boolean>}
         */
        confirm(message, title = '确认操作', type = 'warning') {
            return new Promise((resolve) => {
                const modal = this.create({
                    type: type,
                    title,
                    message,
                    buttons: [
                        {
                            text: '取消',
                            class: 'modal-btn--secondary',
                            onClick: () => {
                                modal.close();
                                resolve(false);
                            }
                        },
                        {
                            text: '确认',
                            class: 'modal-btn--primary',
                            onClick: () => {
                                modal.close();
                                resolve(true);
                            }
                        }
                    ]
                });
            });
        },

        /**
         * 警告弹窗
         * @param {string} message - 警告消息
         * @param {string} title - 弹窗标题
         * @returns {Promise<void>}
         */
        alert(message, title = '提示', type = 'info') {
            return new Promise((resolve) => {
                const modal = this.create({
                    type,
                    title,
                    message,
                    buttons: [
                        {
                            text: '知道了',
                            class: 'modal-btn--primary',
                            onClick: () => {
                                modal.close();
                                resolve();
                            }
                        }
                    ]
                });
            });
        },

        /**
         * 用户操作选择弹窗
         * @param {Object} user - 用户信息
         * @param {Array} actions - 操作列表
         * @returns {Promise<string|null>} 选中的操作key
         */
        userActions(user, actions) {
            return new Promise((resolve) => {
                const modal = this.create({
                    type: 'info',
                    title: '用户操作',
                    user,
                    actions,
                    buttons: [
                        {
                            text: '取消',
                            class: 'modal-btn--ghost',
                            onClick: () => {
                                modal.close();
                                resolve(null);
                            }
                        }
                    ]
                });

                // 保存resolve以便在操作点击时调用
                modal._actionResolve = resolve;
            });
        }
    };

    // ===== 弹窗类 =====
    class Modal {
        constructor(options = {}) {
            this.options = {
                type: options.type || 'info', // info, success, warning, danger
                title: options.title || '',
                message: options.message || '',
                html: options.html || null, // 直接插入的 HTML 内容
                user: options.user || null,
                actions: options.actions || [],
                buttons: options.buttons || [],
                size: options.size || 'md', // sm, md, lg, xl, full
                closable: options.closable !== false,
                closeOnOverlay: options.closeOnOverlay !== false,
                closeOnEscape: options.closeOnEscape !== false,
                onShow: options.onShow || null,
                onClose: options.onClose || null
            };

            this.element = null;
            this.overlay = null;
            this.isOpen = false;
            this.previousActiveElement = null;

            this._build();
            this._bindEvents();
        }

        /**
         * 构建弹窗DOM
         */
        _build() {
            // 创建遮罩层
            this.overlay = document.createElement('div');
            this.overlay.className = 'modal-overlay';

            // 创建弹窗容器
            this.element = document.createElement('div');
            this.element.className = `modal modal--${this.options.size}`;
            this.element.setAttribute('role', 'dialog');
            this.element.setAttribute('aria-modal', 'true');
            this.element.setAttribute('aria-labelledby', 'modal-title');

            // 构建弹窗内容
            let html = this._buildHeader();
            html += this._buildBody();
            html += this._buildFooter();

            this.element.innerHTML = html;

            // 将弹窗添加到遮罩层
            this.overlay.appendChild(this.element);
        }

        /**
         * 构建弹窗头部
         */
        _buildHeader() {
            const icons = {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                danger: '🗑️'
            };

            const icon = icons[this.options.type] || icons.info;

            let html = `
                <div class="modal-header">
                    <h2 class="modal-title" id="modal-title">
                        <span class="modal-icon modal-icon--${this.options.type}">${icon}</span>
                        ${this._escapeHtml(this.options.title)}
                    </h2>
            `;

            if (this.options.closable) {
                html += `
                    <button type="button" class="modal-close" aria-label="关闭弹窗">
                        <span></span>
                    </button>
                `;
            }

            html += '</div>';
            return html;
        }

        /**
         * 构建弹窗内容
         */
        _buildBody() {
            let html = '<div class="modal-body">';

            // 如果提供了 html 参数，直接使用
            if (this.options.html) {
                html += this.options.html;
            } else {
                // 消息描述
                if (this.options.message) {
                    html += `<p class="modal-description">${this._escapeHtml(this.options.message)}</p>`;
                }

                // 用户信息
                if (this.options.user) {
                    html += this._buildUserInfo();
                }

                // 操作列表
                if (this.options.actions && this.options.actions.length > 0) {
                    html += this._buildActionList();
                }
            }

            html += '</div>';
            return html;
        }

        /**
         * 构建用户信息
         */
        _buildUserInfo() {
            const user = this.options.user;
            const avatarLetter = (user.username || '?').charAt(0).toUpperCase();

            return `
                <div class="modal-user-info">
                    <div class="modal-user-avatar">${avatarLetter}</div>
                    <div class="modal-user-details">
                        <div class="modal-user-name">${this._escapeHtml(user.username || '未知用户')}</div>
                        <div class="modal-user-email">${this._escapeHtml(user.email || user.phone || '无联系方式')}</div>
                    </div>
                </div>
            `;
        }

        /**
         * 构建操作列表
         */
        _buildActionList() {
            let html = '<div class="modal-action-list">';

            this.options.actions.forEach(action => {
                html += `
                    <div class="modal-action-item" data-action="${this._escapeHtml(action.key)}">
                        <div class="modal-action-item-icon">${action.icon || '⚡'}</div>
                        <div class="modal-action-item-content">
                            <div class="modal-action-item-title">${this._escapeHtml(action.title)}</div>
                            ${action.description ? `<div class="modal-action-item-desc">${this._escapeHtml(action.description)}</div>` : ''}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            return html;
        }

        /**
         * 构建弹窗底部
         */
        _buildFooter() {
            if (this.options.buttons.length === 0) {
                return '';
            }

            let html = '<div class="modal-footer">';

            this.options.buttons.forEach(button => {
                const btnClass = button.class || 'modal-btn--secondary';
                html += `
                    <button type="button" class="modal-btn ${btnClass}" data-button-index>
                        ${button.loading ? '<span class="modal-btn-loading"></span>' : ''}
                        ${button.icon ? `<span>${button.icon}</span>` : ''}
                        ${this._escapeHtml(button.text)}
                    </button>
                `;
            });

            html += '</div>';
            return html;
        }

        /**
         * 绑定事件
         */
        _bindEvents() {
            // 关闭按钮
            const closeBtn = this.element.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }

            // 遮罩层点击
            if (this.options.closeOnOverlay) {
                this.overlay.addEventListener('click', (e) => {
                    if (e.target === this.overlay) {
                        this.close();
                    }
                });
            }

            // ESC键关闭
            if (this.options.closeOnEscape) {
                this._handleEscape = (e) => {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                    }
                };
                document.addEventListener('keydown', this._handleEscape);
            }

            // 按钮点击
            const buttons = this.element.querySelectorAll('.modal-btn[data-button-index]');
            buttons.forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    const buttonConfig = this.options.buttons[index];
                    if (buttonConfig && buttonConfig.onClick) {
                        buttonConfig.onClick(this);
                    }
                });
            });

            // 操作项点击
            const actionItems = this.element.querySelectorAll('.modal-action-item');
            actionItems.forEach(item => {
                item.addEventListener('click', () => {
                    const actionKey = item.getAttribute('data-action');
                    const action = this.options.actions.find(a => a.key === actionKey);
                    
                    if (action) {
                        if (action.onClick) {
                            action.onClick(this, action);
                        } else if (this._actionResolve) {
                            this.close();
                            this._actionResolve(actionKey);
                        }
                    }
                });
            });

            // 焦点陷阱
            this._handleTab = (e) => {
                if (e.key !== 'Tab' || !this.isOpen) return;

                const focusableElements = this.element.querySelectorAll(ModalManager.focusableElements);
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            };

            this.element.addEventListener('keydown', this._handleTab);
        }

        /**
         * 显示弹窗
         */
        show() {
            if (this.isOpen) return;

            // 保存当前焦点元素
            this.previousActiveElement = document.activeElement;

            // 添加到DOM
            document.body.appendChild(this.overlay);

            // 触发重排以启动动画
            this.overlay.offsetHeight;

            // 显示
            this.isOpen = true;
            document.body.style.overflow = 'hidden';

            // 聚焦到第一个可聚焦元素
            setTimeout(() => {
                const firstFocusable = this.element.querySelector(ModalManager.focusableElements);
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }, 100);

            // 触发显示回调
            if (this.options.onShow) {
                this.options.onShow(this);
            }
        }

        /**
         * 关闭弹窗
         */
        close() {
            if (!this.isOpen) return;

            // 添加关闭动画类
            this.overlay.classList.add('modal-overlay--closing');
            this.element.classList.add('modal--closing');

            // 等待动画结束
            setTimeout(() => {
                // 从DOM移除
                if (this.overlay.parentNode) {
                    this.overlay.parentNode.removeChild(this.overlay);
                }

                // 恢复body滚动
                document.body.style.overflow = '';

                // 恢复焦点
                if (this.previousActiveElement) {
                    this.previousActiveElement.focus();
                }

                this.isOpen = false;

                // 触发关闭回调
                if (this.options.onClose) {
                    this.options.onClose(this);
                }

                // 清理事件监听
                if (this._handleEscape) {
                    document.removeEventListener('keydown', this._handleEscape);
                }
                if (this._handleTab) {
                    this.element.removeEventListener('keydown', this._handleTab);
                }
            }, 200);
        }

        /**
         * 设置按钮加载状态
         */
        setButtonLoading(buttonIndex, loading) {
            const buttons = this.element.querySelectorAll('.modal-btn');
            const button = buttons[buttonIndex];
            
            if (button) {
                if (loading) {
                    button.classList.add('modal-btn--loading');
                    button.disabled = true;
                } else {
                    button.classList.remove('modal-btn--loading');
                    button.disabled = false;
                }
            }
        }

        /**
         * HTML转义
         */
        _escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // ===== 全局API =====
    // 确保 ModalManager 在作用域内
    const manager = ModalManager;
    
    // 直接暴露 ModalManager 的方法
    window.Modal = {
        create: function(options) {
            if (manager && typeof manager.create === 'function') {
                return manager.create.call(manager, options);
            }
            console.error('ModalManager.create is not available', manager);
            return null;
        },
        confirm: function(message, title, type) {
            if (manager && typeof manager.confirm === 'function') {
                return manager.confirm.call(manager, message, title, type);
            }
            console.error('ModalManager.confirm is not available', manager);
            // 降级到原生 confirm
            return Promise.resolve(confirm(message || title || '确认操作'));
        },
        alert: function(message, title, type) {
            if (manager && typeof manager.alert === 'function') {
                return manager.alert.call(manager, message, title, type);
            }
            console.error('ModalManager.alert is not available', manager);
            // 降级到原生 alert
            alert(message || title || '提示');
            return Promise.resolve();
        },
        userActions: function(user, actions) {
            if (manager && typeof manager.userActions === 'function') {
                return manager.userActions.call(manager, user, actions);
            }
            console.error('ModalManager.userActions is not available', manager);
            return Promise.resolve(null);
        },
        // 保留原始对象引用以便调试和备用
        _manager: manager
    };
    
    // 验证所有方法是否可用
    const methods = ['create', 'confirm', 'alert', 'userActions'];
    methods.forEach(method => {
        if (typeof window.Modal[method] !== 'function') {
            console.error(`Failed to bind Modal.${method}`);
        }
    });
    
    if (typeof window.Modal.create === 'function' && typeof window.Modal.confirm === 'function') {
        console.log('✓ Modal API successfully initialized');
    } else {
        console.error('Modal API initialization failed', window.Modal);
    }
    
    // 触发自定义事件，通知其他脚本 Modal 已准备好
    if (typeof document !== 'undefined') {
        const event = new CustomEvent('modalReady', { detail: { Modal: window.Modal } });
        // 使用 setTimeout 确保事件在下一个事件循环中触发
        setTimeout(() => {
            document.dispatchEvent(event);
        }, 0);
    }

    // ===== 用户管理专用API =====
    window.UserActionModal = {
        /**
         * 显示用户操作选择弹窗
         * 直接实现弹窗逻辑，不依赖 Modal.userActions
         */
        show(user, status) {
            return new Promise((resolve) => {
                try {
                    // 根据用户状态定义可用操作
                    const actions = this._getActionsForStatus(status);

                    // 确保 Modal 已加载
                    if (!window.Modal || !window.Modal.create) {
                        console.error('Modal.create is not available');
                        resolve(null);
                        return;
                    }

                    // 直接使用 Modal.create 创建弹窗，不依赖 userActions 方法
                    const modal = window.Modal.create({
                        type: 'info',
                        title: '用户操作',
                        user: user,
                        actions: actions,
                        buttons: [
                            {
                                text: '取消',
                                class: 'modal-btn--ghost',
                                onClick: () => {
                                    modal.close();
                                    resolve(null);
                                }
                            }
                        ]
                    });

                    // 保存resolve以便在操作点击时调用
                    modal._actionResolve = resolve;
                } catch (error) {
                    console.error('Error showing user action modal:', error);
                    resolve(null);
                }
            });
        },

        /**
         * 根据状态获取可用操作
         */
        _getActionsForStatus(status) {
            const actionMap = {
                active: [
                    { key: 'view', icon: '👁️', title: '查看详情', description: '查看用户完整信息' },
                    { key: 'edit', icon: '✏️', title: '编辑用户', description: '修改用户信息' },
                    { key: 'disable', icon: '⏸️', title: '禁用用户', description: '暂时禁用用户账号' },
                    { key: 'freeze', icon: '❄️', title: '冻结用户', description: '冻结用户账号和资产' },
                    { key: 'delete', icon: '🗑️', title: '删除用户', description: '永久删除用户账号' }
                ],
                disabled: [
                    { key: 'view', icon: '👁️', title: '查看详情', description: '查看用户完整信息' },
                    { key: 'edit', icon: '✏️', title: '编辑用户', description: '修改用户信息' },
                    { key: 'enable', icon: '▶️', title: '启用用户', description: '恢复用户账号' },
                    { key: 'freeze', icon: '❄️', title: '冻结用户', description: '冻结用户账号和资产' },
                    { key: 'delete', icon: '🗑️', title: '删除用户', description: '永久删除用户账号' }
                ],
                frozen: [
                    { key: 'view', icon: '👁️', title: '查看详情', description: '查看用户完整信息' },
                    { key: 'edit', icon: '✏️', title: '编辑用户', description: '修改用户信息' },
                    { key: 'unfreeze', icon: '🔥', title: '解冻用户', description: '解除用户冻结状态' },
                    { key: 'delete', icon: '🗑️', title: '删除用户', description: '永久删除用户账号' }
                ],
                deleted: [
                    { key: 'view', icon: '👁️', title: '查看详情', description: '查看用户完整信息' },
                    { key: 'restore', icon: '🔄', title: '恢复用户', description: '恢复已删除的用户' }
                ]
            };

            return actionMap[status] || actionMap.active;
        },

        /**
         * 执行用户操作
         */
        async executeAction(userId, action, user) {
            const actionMessages = {
                view: { title: '查看详情', message: `即将跳转到用户详情页面` },
                edit: { title: '编辑用户', message: `即将跳转到用户编辑页面` },
                enable: { title: '启用用户', message: `确定要启用用户 "${user.username}" 吗？` },
                disable: { title: '禁用用户', message: `确定要禁用用户 "${user.username}" 吗？` },
                freeze: { title: '冻结用户', message: `确定要冻结用户 "${user.username}" 吗？` },
                unfreeze: { title: '解冻用户', message: `确定要解冻用户 "${user.username}" 吗？` },
                delete: { title: '删除用户', message: `确定要删除用户 "${user.username}" 吗？此操作不可恢复！`, type: 'danger' },
                restore: { title: '恢复用户', message: `确定要恢复用户 "${user.username}" 吗？` }
            };

            const msg = actionMessages[action];
            if (!msg) return false;

            // 对于查看和编辑操作，使用弹窗
            if (action === 'view') {
                if (window.showUserDetailsModal) {
                    window.showUserDetailsModal(userId);
                } else {
                    const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
                    window.location.href = `/${adminPrefix}/crm/user/${userId}`;
                }
                return true;
            }
            
            if (action === 'edit') {
                if (window.showEditUserModal) {
                    window.showEditUserModal(userId);
                } else {
                    const adminPrefix = window.ADMIN_PREFIX || window.adminPrefix || 'admin';
                    window.location.href = `/${adminPrefix}/crm/user/${userId}/edit`;
                }
                return true;
            }

            // 对于危险操作，需要二次确认
            if (window.Modal && typeof window.Modal.confirm === 'function') {
                const confirmed = await window.Modal.confirm(msg.message, msg.title, msg.type || 'warning');
                return confirmed;
            } else {
                // 降级处理：使用原生 confirm
                console.warn('Modal.confirm not available, using native confirm');
                return confirm(msg.message);
            }
        }
    };

    // ===== 自动初始化 =====
    // 为现有的操作按钮添加弹窗功能
    document.addEventListener('DOMContentLoaded', () => {
        // 查找所有用户操作按钮
        const actionButtons = document.querySelectorAll('[data-user-action]');
        
        actionButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const userId = btn.getAttribute('data-user-id');
                const action = btn.getAttribute('data-user-action');
                const userName = btn.getAttribute('data-user-name') || '该用户';
                const userStatus = btn.getAttribute('data-user-status') || 'active';
                
                const user = {
                    id: userId,
                    username: userName,
                    status: userStatus
                };

                const confirmed = await UserActionModal.executeAction(userId, action, user);
                
                if (confirmed && action !== 'view' && action !== 'edit') {
                    // 执行操作
                    const actionUrls = {
                        enable: `/admin/crm/user/${userId}/toggle`,
                        disable: `/admin/crm/user/${userId}/toggle`,
                        freeze: `/admin/crm/user/${userId}/freeze`,
                        unfreeze: `/admin/crm/user/${userId}/unfreeze`,
                        delete: `/admin/crm/user/${userId}/delete`,
                        restore: `/admin/crm/user/${userId}/restore`
                    };

                    const url = actionUrls[action];
                    if (url) {
                        window.location.href = url;
                    }
                }
            });
        });
    });

})();
