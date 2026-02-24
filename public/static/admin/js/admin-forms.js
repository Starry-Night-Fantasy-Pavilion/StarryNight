/**
 * 后台管理表单处理脚本
 * 处理表单验证、提交、确认对话框等通用功能
 */

document.addEventListener('DOMContentLoaded', function () {
    initFormValidation();
    initConfirmDialogs();
    initDeleteConfirmations();
    initAjaxForms();
    initAutoSave();
});

/**
 * 初始化表单验证
 */
function initFormValidation() {
    var forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var isValid = true;
            var requiredFields = form.querySelectorAll('[required]');

            requiredFields.forEach(function (field) {
                if (!String(field.value || '').trim()) {
                    isValid = false;
                    showFieldError(field, '此字段为必填项');
                } else {
                    clearFieldError(field);
                }
            });

            // 邮箱验证
            var emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(function (field) {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, '请输入有效的邮箱地址');
                }
            });

            // 数字验证
            var numberFields = form.querySelectorAll('input[type="number"]');
            numberFields.forEach(function (field) {
                if (field.value && isNaN(Number(field.value))) {
                    isValid = false;
                    showFieldError(field, '请输入有效的数字');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 显示字段错误信息
 */
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');

    var errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;

    field.parentNode.appendChild(errorDiv);
}

/**
 * 清除字段错误信息
 */
function clearFieldError(field) {
    field.classList.remove('error');
    var errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * 验证邮箱格式
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * 初始化确认对话框
 */
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            var message = this.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 初始化删除确认
 */
function initDeleteConfirmations() {
    document.querySelectorAll('.btn-delete, [data-action="delete"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var itemName = this.getAttribute('data-item-name') || '此项';
            if (!window.confirm('确定要删除 ' + itemName + ' 吗？此操作不可恢复。')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 初始化 AJAX 表单
 */
function initAjaxForms() {
    document.querySelectorAll('form[data-ajax]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var submitBtn = form.querySelector('[type="submit"]');
            var originalText = submitBtn ? submitBtn.textContent : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '提交中...';
            }

            try {
                var formData = new FormData(form);
                var response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var result = await response.json();

                if (result.success) {
                    showNotification('success', result.message || '操作成功');
                    if (result.redirect) {
                        setTimeout(function () { window.location.href = result.redirect; }, 1000);
                    }
                    if (result.reload) {
                        setTimeout(function () { window.location.reload(); }, 1000);
                    }
                } else {
                    showNotification('error', result.message || '操作失败');
                }
            } catch (error) {
                console.error(error);
                showNotification('error', '网络错误，请稍后重试');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        });
    });
}

/**
 * 初始化自动保存
 */
function initAutoSave() {
    document.querySelectorAll('form[data-autosave]').forEach(function (form) {
        var interval = parseInt(form.getAttribute('data-autosave'), 10) || 30000;
        var autoSaveTimer;

        form.addEventListener('input', function () {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function () {
                autoSaveForm(form);
            }, interval);
        });
    });
}

/**
 * 自动保存表单
 */
async function autoSaveForm(form) {
    var formData = new FormData(form);
    formData.append('_autosave', '1');

    try {
        var response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        var result = await response.json();
        if (result.success) {
            showNotification('info', '自动保存成功', 2000);
        }
    } catch (error) {
        console.error('Auto save failed:', error);
    }
}

/**
 * 显示通知消息
 */
function showNotification(type, message, duration) {
    if (duration === void 0) {
        duration = 3000;
    }

    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(function () { notification.classList.add('show'); }, 10);

    setTimeout(function () {
        notification.classList.remove('show');
        setTimeout(function () { notification.remove(); }, 300);
    }, duration);
}

/**
 * 切换开关状态
 */
function toggleSwitch(checkbox) {
    var target = document.querySelector(checkbox.getAttribute('data-target'));
    if (target) {
        target.style.display = checkbox.checked ? 'block' : 'none';
    }
}

/**
 * 全选/取消全选
 */
function toggleSelectAll(checkbox, name) {
    document.querySelectorAll(`input[type="checkbox"][name="${name}[]"]`).forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

/**
 * 批量操作
 */
function batchAction(action, message) {
    var selected = document.querySelectorAll('input[name="ids[]"]:checked');
    if (selected.length === 0) {
        showNotification('error', '请至少选择一项');
        return false;
    }
    
    if (message && !confirm(message)) {
        return false;
    }
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'batch_action';
    actionInput.value = action;
    form.appendChild(actionInput);
    
    selected.forEach(cb => {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}
