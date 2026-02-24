/**
 * 插件管理页面动态效果
 * 包含：模态框、AJAX提交、操作反馈
 */
document.addEventListener('DOMContentLoaded', () => {
    // ========== 模态框功能 ==========
    var modalOverlay = document.getElementById('plugin-config-modal');
    if (!modalOverlay) return;

    var modalTitle = document.getElementById('plugin-modal-title');
    var iframe = document.getElementById('plugin-modal-iframe');
    var closeModalBtn = document.getElementById('plugin-modal-close');

    var openModal = (url, title) => {
        document.body.appendChild(modalOverlay);
        modalTitle.textContent = title ? `${title} - 配置` : '插件配置';
        var sep = url.includes('?') ? '&' : '?';
        iframe.src = url + sep + '__t=' + Date.now();
        modalOverlay.classList.add('visible');

        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
        document.body.style.setProperty('overflow', 'hidden', 'important');
    };

    iframe.addEventListener('load', () => {
        try {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            if (doc && doc.location.href !== 'about:blank') {
                ['admin-plugin-modal-theme', 'admin-plugin-modal-base', 'admin-plugin-modal-inline'].forEach(id => {
                    var el = doc.getElementById(id);
                    if (el) el.remove();
                });

                var base = doc.createElement('link');
                base.id = 'admin-plugin-modal-base';
                base.rel = 'stylesheet';
                base.href = '/static/admin/css/style.css?v=' + new Date().getTime();
                doc.head.appendChild(base);

                var theme = doc.createElement('link');
                theme.id = 'admin-plugin-modal-theme';
                theme.rel = 'stylesheet';
                theme.href = '/static/admin/css/plugin-modal.css?v=' + new Date().getTime();
                doc.head.appendChild(theme);

                var inline = doc.createElement('style');
                inline.id = 'admin-plugin-modal-inline';
                inline.textContent = `
                    html {
                        overflow-y: auto !important;
                        overflow-x: hidden !important;
                        background: transparent !important;
                        scrollbar-width: thin !important;
                        scrollbar-color: rgba(255, 255, 255, 0.3) transparent !important;
                    }
                    body {
                        background: transparent !important;
                        background-image: none !important;
                        padding: 24px !important;
                        margin: 0 !important;
                        height: auto !important;
                        min-height: 100% !important;
                        overflow: visible !important;
                    }
                    body::before, body::after { display: none !important; }
                    ::-webkit-scrollbar {
                        width: 10px !important;
                        height: 10px !important;
                        display: block !important;
                    }
                    ::-webkit-scrollbar-track {
                        background: rgba(255, 255, 255, 0.05) !important;
                        border-radius: 10px !important;
                    }
                    ::-webkit-scrollbar-thumb {
                        background: rgba(255, 255, 255, 0.25) !important;
                        border-radius: 10px !important;
                        border: 2px solid transparent !important;
                        background-clip: content-box !important;
                    }
                    ::-webkit-scrollbar-thumb:hover {
                        background: rgba(255, 255, 255, 0.4) !important;
                    }
                    * {
                        scrollbar-width: inherit !important;
                        -ms-overflow-style: inherit !important;
                    }
                `;
                doc.head.appendChild(inline);
            }
        } catch (e) {
            console.warn('Cannot inject styles into plugin frame:', e);
        }
    });

    var closeModal = () => {
        modalOverlay.classList.remove('visible');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        setTimeout(() => {
            iframe.src = 'about:blank';
        }, 300);
    };

    document.addEventListener('click', (e) => {
        var button = e.target.closest('.open-plugin-modal');
        if (button) {
            var url = button.dataset.url;
            var title = button.dataset.title;
            if (url) {
                openModal(url, title);
            }
        }
    });

    closeModalBtn.addEventListener('click', closeModal);

    document.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('visible')) {
            closeModal();
        }
    });

    window.addEventListener('message', (e) => {
        if (e.data && e.data.type === 'close-plugin-modal') {
            closeModal();
        }
    });

    // ========== 插件操作动态效果 ==========
    
    // 创建 Toast 提示容器
    var toastContainer = document.getElementById('plugin-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'plugin-toast-container';
        toastContainer.className = 'pm-toast-container';
        document.body.appendChild(toastContainer);
    }

    // 显示 Toast 提示
    function showToast(message, type = 'info', duration = 3000) {
        var toast = document.createElement('div');
        toast.className = 'pm-toast pm-toast-' + type;
        
        var icon = '';
        switch (type) {
            case 'success':
                icon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>';
                break;
            case 'error':
                icon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                break;
            case 'warning':
                icon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>';
                break;
            default:
                icon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';
        }
        
        toast.innerHTML = '<span class="pm-toast-icon">' + icon + '</span><span class="pm-toast-message">' + message + '</span>';
        toastContainer.appendChild(toast);
        
        // 触发动画
        requestAnimationFrame(() => {
            toast.classList.add('pm-toast-show');
        });
        
        // 自动关闭
        setTimeout(() => {
            toast.classList.remove('pm-toast-show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    // AJAX 提交表单
    function submitFormAjax(form, button, successMessage) {
        var action = form.getAttribute('action');
        var method = form.getAttribute('method') || 'POST';
        var formData = new FormData(form);
        
        // 添加 AJAX 标识
        formData.append('_ajax', '1');
        
        fetch(action, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // 检查响应类型
            var contentType = response.headers.get('content-type');
            if (contentType && contentType.indexOf('application/json') !== -1) {
                return response.json();
            }
            // 如果不是 JSON，可能是重定向或 HTML 响应
            return response.text().then(text => {
                // 尝试解析为 JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    // 如果不是 JSON，返回一个表示成功的对象（兼容旧的重定向方式）
                    return { success: true, redirect: true };
                }
            });
        })
        .then(data => {
            if (data.success) {
                showToast(successMessage, 'success');
                
                // 所有操作成功后都刷新页面，确保状态完全同步
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                showToast(data.message || '操作失败', 'error');
            }
        })
        .catch(error => {
            console.error('Request failed:', error);
            showToast('网络错误，请稍后重试', 'error');
        });
    }

    // 监听表单提交
    document.addEventListener('submit', (e) => {
        var form = e.target;
        if (!form.matches('.pm-actions form')) return;
        
        var action = form.getAttribute('action') || '';
        
        // 只处理插件操作表单
        if (!action.includes('/plugins/install') && 
            !action.includes('/plugins/uninstall') && 
            !action.includes('/plugins/toggle')) {
            return;
        }
        
        e.preventDefault();
        
        var button = form.querySelector('button[type="submit"]');
        if (!button) return;
        
        // 确定操作类型和成功消息
        var successMessage = '操作成功';
        if (action.includes('/install')) {
            successMessage = '插件安装成功';
        } else if (action.includes('/uninstall')) {
            successMessage = '插件卸载成功';
        } else if (action.includes('/toggle')) {
            var currentText = button.textContent.trim();
            if (currentText.includes('启用')) {
                successMessage = '插件已启用';
            } else {
                successMessage = '插件已禁用';
            }
        }
        
        submitFormAjax(form, button, successMessage);
    });

    // 添加确认对话框（卸载操作）
    document.addEventListener('click', (e) => {
        var button = e.target.closest('.pm-actions form[action*="/uninstall"] button[type="submit"]');
        if (button) {
            var confirmed = confirm('确定要卸载此插件吗？卸载后将删除插件相关数据。');
            if (!confirmed) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }, true);
});
