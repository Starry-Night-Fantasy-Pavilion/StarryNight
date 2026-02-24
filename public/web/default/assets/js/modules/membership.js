// 会员体系JavaScript功能

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    initializeMembership();
});

/**
 * 初始化会员体系功能
 */
function initializeMembership() {
    // 初始化工具提示
    initializeTooltips();
    
    // 初始化动画效果
    initializeAnimations();
    
    // 初始化表单验证
    initializeFormValidation();
    
    // 初始化AJAX请求
    initializeAjaxRequests();
    
    // 初始化实时数据更新
    initializeRealtimeUpdates();
}

/**
 * 初始化工具提示
 */
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            showTooltip(e.target, e.target.getAttribute('data-tooltip'));
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip();
        });
    });
}

/**
 * 显示工具提示
 */
function showTooltip(element, text) {
    // 移除已存在的工具提示
    hideTooltip();
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
}

/**
 * 隐藏工具提示
 */
function hideTooltip() {
    const existingTooltip = document.querySelector('.tooltip');
    if (existingTooltip) {
        existingTooltip.remove();
    }
}

/**
 * 初始化动画效果
 */
function initializeAnimations() {
    // 数字动画效果
    animateNumbers();
    
    // 进度条动画
    animateProgressBars();
    
    // 卡片悬停效果
    animateCardHovers();
}

/**
 * 数字动画效果
 */
function animateNumbers() {
    const numberElements = document.querySelectorAll('.amount, .balance-amount .amount, .stat-value');
    
    numberElements.forEach(element => {
        const finalValue = element.textContent.replace(/[^0-9]/g, '');
        const duration = 1000; // 动画持续时间（毫秒）
        const steps = 60; // 动画步数
        const stepValue = parseInt(finalValue) / steps;
        let currentValue = 0;
        let step = 0;
        
        const timer = setInterval(() => {
            currentValue += stepValue;
            step++;
            
            if (step >= steps) {
                currentValue = parseInt(finalValue);
                clearInterval(timer);
            }
            
            element.textContent = formatNumber(Math.floor(currentValue));
        }, duration / steps);
    });
}

/**
 * 格式化数字
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * 进度条动画
 */
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1s ease-out';
            bar.style.width = targetWidth;
        }, 100);
    });
}

/**
 * 卡片悬停效果
 */
function animateCardHovers() {
    const cards = document.querySelectorAll('.package-card, .status-card, .benefit-item');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
}

/**
 * 初始化表单验证
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                showFormErrors(form);
            }
        });
        
        // 实时验证
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(input);
            });
        });
    });
}

/**
 * 验证表单
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * 验证字段
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    // 检查必填字段
    if (required && !value) {
        return false;
    }
    
    // 根据字段类型进行验证
    switch (type) {
        case 'email':
            return validateEmail(value);
        case 'number':
            return validateNumber(value);
        case 'tel':
            return validatePhone(value);
        default:
            return value.length > 0;
    }
}

/**
 * 验证邮箱
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * 验证数字
 */
function validateNumber(number) {
    return !isNaN(number) && number > 0;
}

/**
 * 验证手机号
 */
function validatePhone(phone) {
    const phoneRegex = /^1[3-9]\d{9}$/;
    return phoneRegex.test(phone);
}

/**
 * 显示表单错误
 */
function showFormErrors(form) {
    const fields = form.querySelectorAll('input, select, textarea');
    
    fields.forEach(field => {
        if (!validateField(field)) {
            showFieldError(field, '此字段为必填项或格式不正确');
        }
    });
}

/**
 * 显示字段错误
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

/**
 * 清除字段错误
 */
function clearFieldError(field) {
    field.classList.remove('error');
    
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * 初始化AJAX请求
 */
function initializeAjaxRequests() {
    // 设置CSRF令牌
    setupCSRFToken();
    
    // 设置全局AJAX默认配置
    setupAjaxDefaults();
}

/**
 * 设置CSRF令牌
 */
function setupCSRFToken() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = csrfToken.getAttribute('content');
            form.appendChild(tokenInput);
        }
    });
}

/**
 * 设置AJAX默认配置
 */
function setupAjaxDefaults() {
    // 设置全局AJAX配置
    const xhr = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
        this.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        this.setRequestHeader('Accept', 'application/json');
        return xhr.call(this, method, url, async, user, pass);
    };
}

/**
 * 初始化实时数据更新
 */
function initializeRealtimeUpdates() {
    // 检查用户余额更新
    checkBalanceUpdates();
    
    // 检查会员状态更新
    checkMembershipUpdates();
    
    // 检查订单状态更新
    checkOrderUpdates();
}

/**
 * 检查余额更新
 */
function checkBalanceUpdates() {
    const balanceElements = document.querySelectorAll('.balance-amount .amount');
    
    if (balanceElements.length > 0) {
        // 每30秒检查一次余额
        setInterval(() => {
            fetch('/membership/getBalance', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    balanceElements.forEach(element => {
                        const currentValue = parseInt(element.textContent.replace(/[^0-9]/g, ''));
                        const newValue = parseInt(data.balance.balance);
                        
                        if (currentValue !== newValue) {
                            animateBalanceChange(element, currentValue, newValue);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Balance update error:', error);
            });
        }, 30000);
    }
}

/**
 * 动画余额变化
 */
function animateBalanceChange(element, fromValue, toValue) {
    const duration = 500;
    const steps = 30;
    const stepValue = (toValue - fromValue) / steps;
    let currentValue = fromValue;
    let step = 0;
    
    const timer = setInterval(() => {
        currentValue += stepValue;
        step++;
        
        if (step >= steps) {
            currentValue = toValue;
            clearInterval(timer);
        }
        
        element.textContent = formatNumber(Math.floor(currentValue));
    }, duration / steps);
}

/**
 * 检查会员状态更新
 */
function checkMembershipUpdates() {
    const membershipElements = document.querySelectorAll('.membership-status, .status-card');
    
    if (membershipElements.length > 0) {
        // 每60秒检查一次会员状态
        setInterval(() => {
            // 这里可以添加会员状态检查逻辑
        }, 60000);
    }
}

/**
 * 检查订单状态更新
 */
function checkOrderUpdates() {
    const orderElements = document.querySelectorAll('.order-status');
    
    if (orderElements.length > 0) {
        // 每30秒检查一次订单状态
        setInterval(() => {
            // 这里可以添加订单状态检查逻辑
        }, 30000);
    }
}

/**
 * 显示加载状态
 */
function showLoading(element, text = '加载中...') {
    element.disabled = true;
    element.dataset.originalText = element.textContent;
    element.textContent = text;
    element.classList.add('loading');
}

/**
 * 隐藏加载状态
 */
function hideLoading(element) {
    element.disabled = false;
    element.textContent = element.dataset.originalText;
    element.classList.remove('loading');
}

/**
 * 显示通知
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // 自动隐藏通知
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * 确认对话框
 */
function confirmDialog(message, callback) {
    const confirmed = confirm(message);
    if (confirmed && callback) {
        callback();
    }
}

/**
 * 格式化货币
 */
function formatCurrency(amount) {
    return '¥' + parseFloat(amount).toFixed(2);
}

/**
 * 格式化日期
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('已复制到剪贴板', 'success');
        }).catch(err => {
            console.error('Copy error:', err);
            showNotification('复制失败', 'error');
        });
    } else {
        // 降级方案
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('已复制到剪贴板', 'success');
        } catch (err) {
            console.error('Copy error:', err);
            showNotification('复制失败', 'error');
        }
        
        document.body.removeChild(textArea);
    }
}

/**
 * 防抖函数
 */
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

/**
 * 节流函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// 导出全局函数
window.MembershipUtils = {
    showLoading,
    hideLoading,
    showNotification,
    confirmDialog,
    formatCurrency,
    formatDate,
    copyToClipboard,
    debounce,
    throttle
};