/**
 * 触摸交互优化脚本
 * 增强移动端的触摸体验
 */

document.addEventListener('DOMContentLoaded', function() {
    initTouchInteractions();
    initGestures();
    initLongPress();
});

/**
 * 初始化触摸交互
 */
function initTouchInteractions() {
    if (!('ontouchstart' in window)) {
        return; // 非触摸设备
    }
    
    // 为所有可点击元素添加触摸反馈
    const clickableElements = document.querySelectorAll('.btn, a, .clickable, [onclick]');
    clickableElements.forEach(element => {
        addTouchFeedback(element);
    });
    
    // 优化表单控件
    const formControls = document.querySelectorAll('input, select, textarea, button');
    formControls.forEach(element => {
        optimizeFormControl(element);
    });
    
    // 防止误触
    preventAccidentalTaps();
}

/**
 * 添加触摸反馈
 */
function addTouchFeedback(element) {
    let touchTimer;
    
    element.addEventListener('touchstart', function(e) {
        // 添加触摸状态
        this.classList.add('touch-feedback', 'active');
        
        // 创建涟漪效果
        createRipple(this, e.touches[0]);
        
        // 设置定时器移除效果
        clearTimeout(touchTimer);
        touchTimer = setTimeout(() => {
            this.classList.remove('active');
        }, 150);
    }, { passive: true });
    
    element.addEventListener('touchend', function(e) {
        // 立即移除触摸状态
        this.classList.remove('active');
        
        // 延迟移除涟漪效果
        setTimeout(() => {
            this.classList.remove('touch-feedback');
        }, 300);
    }, { passive: true });
    
    element.addEventListener('touchcancel', function() {
        // 触摸取消时移除所有效果
        this.classList.remove('touch-feedback', 'active');
        clearTimeout(touchTimer);
    }, { passive: true });
}

/**
 * 创建涟漪效果
 */
function createRipple(element, touch) {
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = touch.clientX - rect.left - size / 2;
    const y = touch.clientY - rect.top - size / 2;
    
    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        left: ${x}px;
        top: ${y}px;
        width: ${size}px;
        height: ${size}px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
        z-index: 1;
    `;
    
    // 确保父元素有相对定位
    const parentStyle = window.getComputedStyle(element);
    if (parentStyle.position !== 'relative' && parentStyle.position !== 'absolute') {
        element.style.position = 'relative';
    }
    
    element.appendChild(ripple);
    
    // 动画结束后移除
    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.remove();
        }
    }, 600);
}

/**
 * 优化表单控件
 */
function optimizeFormControl(element) {
    // 防止iOS缩放
    if (element.type === 'text' || element.type === 'email' || element.type === 'password' || element.type === 'tel') {
        element.style.fontSize = '16px';
    }
    
    // 添加触摸反馈
    element.addEventListener('touchstart', function() {
        this.style.transform = 'scale(0.98)';
    }, { passive: true });
    
    element.addEventListener('touchend', function() {
        const self = this;
        setTimeout(() => {
            self.style.transform = '';
        }, 100);
    }, { passive: true });
    
    // 优化焦点体验
    element.addEventListener('focus', function() {
        this.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    });
}

/**
 * 防止误触
 */
function preventAccidentalTaps() {
    let lastTapTime = 0;
    let lastTapElement = null;
    
    document.addEventListener('touchend', function(e) {
        const currentTime = Date.now();
        const currentElement = e.target;
        
        // 检测双击
        if (lastTapElement === currentElement && currentTime - lastTapTime < 300) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        lastTapTime = currentTime;
        lastTapElement = currentElement;
    });
}

/**
 * 初始化手势支持
 */
function initGestures() {
    let startX = 0;
    let startY = 0;
    let startTime = 0;
    
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTime = Date.now();
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        const endX = e.changedTouches[0].clientX;
        const endY = e.changedTouches[0].clientY;
        const endTime = Date.now();
        
        const deltaX = endX - startX;
        const deltaY = endY - startY;
        const deltaTime = endTime - startTime;
        
        // 检测滑动手势
        if (Math.abs(deltaX) > 50 && Math.abs(deltaY) < 30 && deltaTime < 500) {
            handleSwipeGesture(deltaX > 0 ? 'right' : 'left', e.target);
        }
        
        // 检测垂直滑动
        if (Math.abs(deltaY) > 50 && Math.abs(deltaX) < 30 && deltaTime < 500) {
            handleVerticalSwipe(deltaY > 0 ? 'down' : 'up', e.target);
        }
    }, { passive: true });
}

/**
 * 处理滑动手势
 */
function handleSwipeGesture(direction, target) {
    // 触发自定义事件
    const event = new CustomEvent('swipe', {
        detail: { direction, target }
    });
    document.dispatchEvent(event);
    
    // 处理特定的滑动操作
    if (direction === 'right') {
        handleRightSwipe(target);
    } else if (direction === 'left') {
        handleLeftSwipe(target);
    }
}

/**
 * 处理垂直滑动
 */
function handleVerticalSwipe(direction, target) {
    const event = new CustomEvent('verticalSwipe', {
        detail: { direction, target }
    });
    document.dispatchEvent(event);
    
    if (direction === 'down') {
        // 向下滑动可能需要隐藏键盘
        hideKeyboard();
    }
}

/**
 * 处理右滑
 */
function handleRightSwipe(target) {
    // 从左边缘右滑打开侧边栏
    if (startX < 20) {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && !sidebar.classList.contains('mobile-open')) {
            openMobileSidebar();
        }
    }
    
    // 处理可滑动元素的右滑操作
    const swipeable = target.closest('.swipeable');
    if (swipeable) {
        handleSwipeableAction(swipeable, 'right');
    }
}

/**
 * 处理左滑
 */
function handleLeftSwipe(target) {
    // 左滑关闭侧边栏
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && sidebar.classList.contains('mobile-open')) {
        closeMobileSidebar();
    }
    
    // 处理可滑动元素的左滑操作
    const swipeable = target.closest('.swipeable');
    if (swipeable) {
        handleSwipeableAction(swipeable, 'left');
    }
}

/**
 * 处理可滑动元素的操作
 */
function handleSwipeableAction(element, direction) {
    const action = element.getAttribute(`data-swipe-${direction}`);
    if (action) {
        // 执行自定义动作
        if (action === 'delete') {
            confirmDelete(element);
        } else if (action === 'edit') {
            navigateToEdit(element);
        } else if (action.startsWith('javascript:')) {
            // 执行JavaScript代码
            try {
                eval(action.replace('javascript:', ''));
            } catch (e) {
                console.error('Swipe action error:', e);
            }
        }
    }
}

/**
 * 初始化长按功能
 */
function initLongPress() {
    let longPressTimer;
    let isLongPress = false;
    
    document.addEventListener('touchstart', function(e) {
        const target = e.target;
        const longPressElement = target.closest('.long-press');
        
        if (!longPressElement) return;
        
        isLongPress = false;
        
        longPressTimer = setTimeout(() => {
            isLongPress = true;
            longPressElement.classList.add('active');
            handleLongPress(longPressElement, e);
            
            // 震动反馈
            if ('vibrate' in navigator) {
                navigator.vibrate(50);
            }
        }, 500);
    }, { passive: true });
    
    document.addEventListener('touchmove', function() {
        // 移动时取消长按
        clearTimeout(longPressTimer);
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        clearTimeout(longPressTimer);
        
        const target = e.target;
        const longPressElement = target.closest('.long-press');
        
        if (longPressElement) {
            longPressElement.classList.remove('active');
            
            // 如果是长按，阻止点击事件
            if (isLongPress) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    });
}

/**
 * 处理长按事件
 */
function handleLongPress(element, event) {
    // 触发自定义长按事件
    const customEvent = new CustomEvent('longpress', {
        detail: { element, originalEvent: event }
    });
    element.dispatchEvent(customEvent);
    
    // 显示上下文菜单
    showContextMenu(element, event);
}

/**
 * 显示上下文菜单
 */
function showContextMenu(element, event) {
    // 移除现有菜单
    const existingMenu = document.querySelector('.touch-context-menu');
    if (existingMenu) {
        existingMenu.remove();
    }
    
    // 创建菜单
    const menu = document.createElement('div');
    menu.className = 'touch-context-menu';
    menu.style.cssText = `
        position: fixed;
        left: ${event.touches[0].clientX}px;
        top: ${event.touches[0].clientY}px;
        background: var(--bg-primary);
        backdrop-filter: var(--glass-blur);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 8px 0;
        min-width: 160px;
        z-index: 9999;
        box-shadow: var(--shadow-lg);
        animation: contextMenuFadeIn 0.2s ease;
    `;
    
    // 添加菜单项
    const menuItems = getContextMenuItems(element);
    menuItems.forEach(item => {
        const menuItem = createContextMenuItem(item);
        menu.appendChild(menuItem);
    });
    
    document.body.appendChild(menu);
    
    // 点击其他地方关闭菜单
    setTimeout(() => {
        document.addEventListener('click', closeContextMenu, { once: true });
    }, 100);
    
    // 调整菜单位置
    adjustMenuPosition(menu);
}

/**
 * 获取上下文菜单项
 */
function getContextMenuItems(element) {
    const items = [];
    
    // 通用菜单项
    items.push({
        text: '复制',
        icon: 'copy',
        action: () => copyToClipboard(element)
    });
    
    // 根据元素类型添加特定菜单项
    if (element.closest('.table-row')) {
        items.push({
            text: '编辑',
            icon: 'edit',
            action: () => editRow(element)
        });
        
        items.push({
            text: '删除',
            icon: 'delete',
            action: () => deleteRow(element)
        });
    }
    
    if (element.href) {
        items.push({
            text: '在新标签页打开',
            icon: 'external',
            action: () => window.open(element.href, '_blank')
        });
    }
    
    return items;
}

/**
 * 创建上下文菜单项
 */
function createContextMenuItem(item) {
    const menuItem = document.createElement('div');
    menuItem.className = 'context-menu-item';
    menuItem.style.cssText = `
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-primary);
        font-size: 14px;
        min-height: 44px;
        transition: background-color 0.2s ease;
    `;
    
    menuItem.innerHTML = `
        <span class="context-menu-icon">${item.icon}</span>
        <span class="context-menu-text">${item.text}</span>
    `;
    
    menuItem.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        item.action();
        closeContextMenu();
    });
    
    menuItem.addEventListener('mouseenter', () => {
        menuItem.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
    });
    
    menuItem.addEventListener('mouseleave', () => {
        menuItem.style.backgroundColor = '';
    });
    
    return menuItem;
}

/**
 * 调整菜单位置
 */
function adjustMenuPosition(menu) {
    const rect = menu.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // 水平调整
    if (rect.right > viewportWidth) {
        menu.style.left = (viewportWidth - rect.width - 16) + 'px';
    }
    
    // 垂直调整
    if (rect.bottom > viewportHeight) {
        menu.style.top = (viewportHeight - rect.height - 16) + 'px';
    }
}

/**
 * 关闭上下文菜单
 */
function closeContextMenu() {
    const menu = document.querySelector('.touch-context-menu');
    if (menu) {
        menu.style.animation = 'contextMenuFadeOut 0.2s ease';
        setTimeout(() => {
            menu.remove();
        }, 200);
    }
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(element) {
    const text = element.textContent || element.value || '';
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('已复制到剪贴板');
        });
    } else {
        // 降级方案
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('已复制到剪贴板');
    }
}

/**
 * 显示提示消息
 */
function showToast(message, duration = 2000) {
    const existingToast = document.querySelector('.touch-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = 'touch-toast';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--bg-primary);
        backdrop-filter: var(--glass-blur);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 12px 20px;
        color: var(--text-primary);
        font-size: 14px;
        z-index: 9999;
        box-shadow: var(--shadow-lg);
        animation: toastSlideUp 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'toastSlideDown 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
}

/**
 * 隐藏键盘
 */
function hideKeyboard() {
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        activeElement.blur();
    }
}

/**
 * 确认删除
 */
function confirmDelete(element) {
    if (confirm('确定要删除这个项目吗？')) {
        // 执行删除操作
        const deleteAction = element.getAttribute('data-delete-action');
        if (deleteAction) {
            window.location.href = deleteAction;
        }
    }
}

/**
 * 导航到编辑页面
 */
function navigateToEdit(element) {
    const editUrl = element.getAttribute('data-edit-url');
    if (editUrl) {
        window.location.href = editUrl;
    }
}

/**
 * 编辑行
 */
function editRow(element) {
    const row = element.closest('tr');
    if (row) {
        const editButton = row.querySelector('.btn-edit, [data-action="edit"]');
        if (editButton) {
            editButton.click();
        }
    }
}

/**
 * 删除行
 */
function deleteRow(element) {
    const row = element.closest('tr');
    if (row) {
        const deleteButton = row.querySelector('.btn-delete, [data-action="delete"]');
        if (deleteButton) {
            if (confirm('确定要删除这条记录吗？')) {
                deleteButton.click();
            }
        }
    }
}

// 添加必要的CSS动画（避免重复添加）
(function() {
    // 检查是否已经添加过样式
    const existingStyle = document.getElementById('touch-interactions-styles');
    if (existingStyle) {
        return; // 如果已经存在，直接返回
    }
    
    const touchInteractionsStyle = document.createElement('style');
    touchInteractionsStyle.id = 'touch-interactions-styles'; // 添加ID以便检查
    touchInteractionsStyle.textContent = `
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        @keyframes contextMenuFadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes contextMenuFadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.8);
            }
        }
        
        @keyframes toastSlideUp {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        
        @keyframes toastSlideDown {
            from {
                opacity: 1;
                transform: translate(-50%, 0);
            }
            to {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
        }
        
        .touch-context-menu {
            backdrop-filter: var(--glass-blur);
        }
        
        .context-menu-icon {
            width: 16px;
            height: 16px;
            opacity: 0.7;
        }
        
        .touch-toast {
            backdrop-filter: var(--glass-blur);
        }
    `;
    document.head.appendChild(touchInteractionsStyle);
})();
