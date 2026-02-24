/**
 * 响应式表单处理脚本
 * 优化表单在移动端的交互体验
 */

document.addEventListener('DOMContentLoaded', function() {
    initResponsiveForms();
    initMobileFormEnhancements();
    initFormValidation();
});

/**
 * 初始化响应式表单
 */
function initResponsiveForms() {
    // 优化所有表单
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        enhanceFormAccessibility(form);
        addFormInteraction(form);
        optimizeFormLayout(form);
    });
}

/**
 * 增强表单的可访问性
 */
function enhanceFormAccessibility(form) {
    // 为表单添加描述
    if (!form.querySelector('fieldset') && !form.getAttribute('aria-label')) {
        const firstLabel = form.querySelector('label');
        if (firstLabel) {
            form.setAttribute('aria-label', `包含${firstLabel.textContent.trim()}等字段的表单`);
        }
    }
    
    // 优化标签关联
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        const id = input.id;
        const label = form.querySelector(`label[for="${id}"]`);
        
        if (!id && label) {
            // 为没有ID的输入框生成ID
            input.id = 'field_' + Math.random().toString(36).substr(2, 9);
            label.setAttribute('for', input.id);
        }
        
        // 添加required属性提示
        if (input.hasAttribute('required') && label) {
            if (!label.querySelector('.required-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'required-indicator';
                indicator.textContent = ' *';
                indicator.setAttribute('aria-label', '必填项');
                indicator.style.color = 'var(--danger-color)';
                label.appendChild(indicator);
            }
        }
        
        // 添加输入类型提示
        addInputTypeHints(input);
    });
}

/**
 * 添加输入类型提示
 */
function addInputTypeHints(input) {
    const type = input.type;
    const placeholder = input.placeholder || '';
    
    // 根据输入类型添加合适的placeholder
    if (!placeholder && type !== 'hidden' && type !== 'submit' && type !== 'button') {
        const typeHints = {
            'email': '请输入邮箱地址',
            'password': '请输入密码',
            'tel': '请输入手机号码',
            'url': '请输入网址',
            'search': '请输入搜索关键词',
            'date': '请选择日期',
            'time': '请选择时间',
            'number': '请输入数字',
            'file': '请选择文件'
        };
        
        if (typeHints[type]) {
            input.placeholder = typeHints[type];
        }
    }
    
    // 添加输入模式
    if (type === 'tel') {
        input.setAttribute('inputmode', 'tel');
        input.setAttribute('pattern', '[0-9]*');
    } else if (type === 'number') {
        input.setAttribute('inputmode', 'numeric');
    } else if (type === 'email') {
        input.setAttribute('inputmode', 'email');
    }
}

/**
 * 添加表单交互功能
 */
function addFormInteraction(form) {
    // 添加输入框焦点效果
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        // 焦点状态
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
            addFocusRipple(this);
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
        
        // 输入实时验证
        input.addEventListener('input', function() {
            validateField(this);
        });
        
        // 移动端触摸优化
        if ('ontouchstart' in window) {
            addTouchOptimization(input);
        }
    });
    

}

/**
 * 添加焦点涟漪效果
 */
function addFocusRipple(element) {
    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(59, 130, 246, 0.3);
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 1;
        transition: width 0.3s ease, height 0.3s ease, opacity 0.3s ease;
    `;
    
    element.parentElement.style.position = 'relative';
    element.parentElement.appendChild(ripple);
    
    // 动画
    requestAnimationFrame(() => {
        ripple.style.width = '100px';
        ripple.style.height = '100px';
        ripple.style.opacity = '0';
    });
    
    // 清理
    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.remove();
        }
    }, 300);
}

/**
 * 添加移动端触摸优化
 */
function addTouchOptimization(input) {
    // 防止iOS缩放
    if (input.type === 'text' || input.type === 'email' || input.type === 'password' || input.type === 'tel') {
        input.style.fontSize = '16px';
    }
    
    // 添加触摸反馈
    input.addEventListener('touchstart', function() {
        this.style.transform = 'scale(0.98)';
    }, { passive: true });
    
    input.addEventListener('touchend', function() {
        const self = this;
        setTimeout(() => {
            self.style.transform = '';
        }, 100);
    }, { passive: true });
}

/**
 * 优化表单布局
 */
function optimizeFormLayout(form) {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // 移动端布局优化
        optimizeMobileLayout(form);
    } else {
        // 桌面端布局优化
        optimizeDesktopLayout(form);
    }
}

/**
 * 移动端布局优化
 */
function optimizeMobileLayout(form) {
    // 将内联标签改为块级标签
    const inlineLabels = form.querySelectorAll('label.inline, .form-label-inline');
    inlineLabels.forEach(label => {
        label.classList.remove('inline', 'form-label-inline');
        label.style.display = 'block';
        label.style.marginBottom = '6px';
    });
    
    // 优化按钮组
    const buttonGroups = form.querySelectorAll('.btn-group');
    buttonGroups.forEach(group => {
        if (group.style.display !== 'flex') {
            group.style.display = 'flex';
            group.style.flexDirection = 'column';
            group.style.gap = '12px';
        }
        
        const buttons = group.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.style.width = '100%';
            btn.style.minHeight = '48px';
        });
    });
    
    // 优化输入组
    const inputGroups = form.querySelectorAll('.input-group');
    inputGroups.forEach(group => {
        group.style.flexDirection = 'column';
        group.style.gap = '8px';
        
        const inputs = group.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.style.borderRadius = 'var(--radius-md)';
        });
        
        const buttons = group.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.style.borderRadius = 'var(--radius-md)';
            btn.style.minHeight = '48px';
        });
    });
}

/**
 * 桌面端布局优化
 */
function optimizeDesktopLayout(form) {
    // 恢复桌面端布局
    const buttonGroups = form.querySelectorAll('.btn-group');
    buttonGroups.forEach(group => {
        if (window.innerWidth > 768) {
            group.style.flexDirection = '';
            group.style.gap = '';
            
            const buttons = group.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.style.width = '';
                btn.style.minHeight = '';
            });
        }
    });
}

/**
 * 初始化移动端表单增强功能
 */
function initMobileFormEnhancements() {
    if ('ontouchstart' in window) {
        initTouchGestures();
        initMobileKeyboard();
        initMobileScroll();
    }
}

/**
 * 初始化触摸手势
 */
function initTouchGestures() {
    let touchStartY = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].clientY;
        handleSwipeGesture(touchStartY, touchEndY);
    }, { passive: true });
    
    function handleSwipeGesture(startY, endY) {
        const swipeDistance = Math.abs(endY - startY);
        
        if (swipeDistance > 100) {
            // 向下滑动，可能需要隐藏键盘
            if (endY > startY) {
                hideMobileKeyboard();
            }
        }
    }
}

/**
 * 初始化移动端键盘处理
 */
function initMobileKeyboard() {
    const inputs = document.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        // 键盘显示时调整布局
        input.addEventListener('focus', function() {
            adjustLayoutForKeyboard(true);
        });
        
        input.addEventListener('blur', function() {
            adjustLayoutForKeyboard(false);
        });
    });
}

/**
 * 调整键盘布局
 */
function adjustLayoutForKeyboard(show) {
    const activeElement = document.activeElement;
    const isInput = activeElement && (
        activeElement.tagName === 'INPUT' || 
        activeElement.tagName === 'TEXTAREA'
    );
    
    if (show && isInput) {
        // 键盘显示时，滚动到输入框
        setTimeout(() => {
            activeElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }, 300);
        
        // 添加键盘显示样式
        document.body.classList.add('keyboard-visible');
    } else {
        // 键盘隐藏时恢复正常
        document.body.classList.remove('keyboard-visible');
    }
}

/**
 * 隐藏移动端键盘
 */
function hideMobileKeyboard() {
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        activeElement.blur();
    }
}

/**
 * 初始化移动端滚动优化
 */
function initMobileScroll() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // 优化表单内滚动
        form.addEventListener('touchmove', function(e) {
            const target = e.target;
            
            // 如果在输入框内，允许滚动
            if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') {
                return;
            }
            
            // 否则阻止默认滚动行为
            e.preventDefault();
        }, { passive: false });
    });
}

/**
 * 初始化表单验证
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        // 实时验证
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                // 清除之前的错误状态
                this.classList.remove('is-invalid');
                const feedback = this.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
    });
}

/**
 * 验证单个字段
 */
function validateField(field) {
    let isValid = true;
    let errorMessage = '';
    
    // 必填验证
    if (field.hasAttribute('required') && !field.value.trim()) {
        isValid = false;
        errorMessage = '此字段为必填项';
    }
    
    // 邮箱验证
    if (field.type === 'email' && field.value) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(field.value)) {
            isValid = false;
            errorMessage = '请输入有效的邮箱地址';
        }
    }
    
    // 手机号验证
    if (field.type === 'tel' && field.value) {
        const phonePattern = /^1[3-9]\d{9}$/;
        if (!phonePattern.test(field.value.replace(/\s/g, ''))) {
            isValid = false;
            errorMessage = '请输入有效的手机号码';
        }
    }
    
    // 最小长度验证
    const minLength = field.getAttribute('minlength');
    if (minLength && field.value.length < parseInt(minLength)) {
        isValid = false;
        errorMessage = `最少需要${minLength}个字符`;
    }
    
    // 显示验证结果
    showFieldValidation(field, isValid, errorMessage);
    
    return isValid;
}

/**
 * 显示字段验证结果
 */
function showFieldValidation(field, isValid, errorMessage) {
    // 移除之前的验证状态
    field.classList.remove('is-valid', 'is-invalid');
    
    // 移除之前的反馈信息
    const existingFeedback = field.parentElement.querySelector('.valid-feedback, .invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    if (isValid) {
        field.classList.add('is-valid');
        
        // 添加成功反馈（可选）
        if (field.hasAttribute('data-show-success')) {
            const feedback = document.createElement('div');
            feedback.className = 'valid-feedback';
            feedback.textContent = '✓ 输入正确';
            field.parentElement.appendChild(feedback);
        }
    } else {
        field.classList.add('is-invalid');
        
        // 添加错误反馈
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = errorMessage;
        field.parentElement.appendChild(feedback);
        
        // 震动效果（如果支持）
        if ('vibrate' in navigator) {
            navigator.vibrate(100);
        }
    }
}

/**
 * 验证整个表单
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * 显示表单错误
 */
function showFormErrors(form) {
    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) {
        // 滚动到第一个错误字段
        firstInvalid.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // 聚焦到错误字段
        firstInvalid.focus();
        
        // 震动提示
        if ('vibrate' in navigator) {
            navigator.vibrate([100, 50, 100]);
        }
    }
    
    // 显示整体错误提示
    showNotification('请检查并修正表单中的错误', 'error');
}

/**
 * 显示通知
 */
function showNotification(message, type = 'info') {
    // 移除现有通知
    const existingNotification = document.querySelector('.form-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // 创建新通知
    const notification = document.createElement('div');
    notification.className = `form-notification alert alert-${type} alert-dismissible`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: calc(100vw - 40px);
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // 自动关闭
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 5000);
    
    // 手动关闭
    const closeBtn = notification.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
    }
}

// 监听窗口大小变化
window.addEventListener('resize', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        optimizeFormLayout(form);
    });
});

// 添加必要的CSS动画（避免重复声明）
if (!document.getElementById('responsive-forms-styles')) {
    const responsiveFormsStyle = document.createElement('style');
    responsiveFormsStyle.id = 'responsive-forms-styles';
    responsiveFormsStyle.textContent = `
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
        
        .keyboard-visible {
            position: fixed;
            width: 100%;
            height: 100vh;
            overflow: hidden;
        }
    `;
    document.head.appendChild(responsiveFormsStyle);
}
