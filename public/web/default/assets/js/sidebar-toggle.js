/**
 * 侧边栏切换脚本
 * 处理侧边栏的展开/收起、移动端适配等功能
 */

// 防止重复初始化
var sidebarInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
    if (sidebarInitialized) return;
    sidebarInitialized = true;
    
    // 先恢复状态，再初始化其他功能
    initSidebarStorage();
    initSidebarToggle();
    initSidebarActiveState();
    initMobileSidebar();
});

/**
 * 初始化侧边栏切换功能
 */
function initSidebarToggle() {
    var toggleBtn = document.querySelector('.sidebar-toggle');
    var sidebar = document.querySelector('.sidebar');
    var mainContent = document.querySelector('.main-content');
    
    if (!toggleBtn || !sidebar) return;
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if (mainContent) {
            mainContent.classList.toggle('expanded');
        }
        
        // 保存状态
        var isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed);
        
        // 触发窗口调整事件，以便图表等组件重新计算尺寸
        window.dispatchEvent(new Event('resize'));
    });
}

/**
 * 初始化侧边栏激活状态
 */
function initSidebarActiveState() {
    var currentPath = window.location.pathname;
    var sidebarLinks = document.querySelectorAll('.sidebar-menu-card a');
    
    sidebarLinks.forEach(function(link) {
        var href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace(/^\//, ''))) {
            link.classList.add('active');
        }
        
        // 为折叠状态添加tooltip
        var navText = link.querySelector('.nav-text');
        if (navText) {
            var tooltipText = navText.textContent.trim();
            link.setAttribute('data-tooltip', tooltipText);
        }
    });
}

/**
 * 初始化移动端侧边栏
 */
function initMobileSidebar() {
    var sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    
    // 创建移动端遮罩层
    var overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // 点击遮罩层关闭侧边栏
    overlay.addEventListener('click', closeMobileSidebar);
    
    // 创建移动端菜单按钮
    var mobileToggle = document.querySelector('.mobile-menu-toggle');
    if (!mobileToggle) {
        mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-menu-toggle';
        mobileToggle.innerHTML = '<span></span><span></span><span></span>';
        mobileToggle.setAttribute('aria-label', '切换菜单');
        
        var topBar = document.querySelector('.top-bar');
        if (topBar) {
            topBar.insertBefore(mobileToggle, topBar.firstChild);
        }
    }
    
    mobileToggle.addEventListener('click', toggleMobileSidebar);
    
    // 添加触摸滑动支持
    addTouchSupport(sidebar, overlay);
    
    // 窗口大小改变时处理
    window.addEventListener('resize', handleResize);
    handleResize();
}

/**
 * 切换移动端侧边栏
 */
function toggleMobileSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar.classList.contains('mobile-open')) {
        closeMobileSidebar();
    } else {
        openMobileSidebar();
    }
}

/**
 * 打开移动端侧边栏
 */
function openMobileSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

/**
 * 关闭移动端侧边栏
 */
function closeMobileSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
}

/**
 * 处理窗口大小改变
 */
function handleResize() {
    var sidebar = document.querySelector('.sidebar');
    var isMobile = window.innerWidth <= 768;
    
    if (!isMobile && sidebar.classList.contains('mobile-open')) {
        closeMobileSidebar();
    }
}

/**
 * 初始化侧边栏存储状态
 */
function initSidebarStorage() {
    var sidebar = document.querySelector('.sidebar');
    var mainContent = document.querySelector('.main-content');
    
    if (!sidebar) return;
    
    // 标记正在恢复状态，防止其他脚本干扰
    sidebar.dataset.restoring = 'true';
    
    // 先禁用过渡效果，避免切换页面时出现双重效果
    sidebar.style.transition = 'none';
    if (mainContent) {
        mainContent.style.transition = 'none';
    }
    
    // 禁用所有子元素的过渡效果
    var sidebarChildren = sidebar.querySelectorAll('*');
    var originalTransitions = new Map();
    sidebarChildren.forEach(child => {
        var computedStyle = window.getComputedStyle(child);
        var transition = computedStyle.transition;
        if (transition && transition !== 'none' && transition !== 'all 0s ease 0s') {
            originalTransitions.set(child, child.style.transition || '');
            child.style.transition = 'none';
        }
    });
    
    // 恢复保存的状态
    var isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('expanded');
        }
    } else {
        sidebar.classList.remove('collapsed');
        if (mainContent) {
            mainContent.classList.remove('expanded');
        }
    }
    
    // 强制重排，确保状态立即应用
    void sidebar.offsetHeight;
    if (mainContent) {
        void mainContent.offsetHeight;
    }
    
    // 使用双重 requestAnimationFrame 确保状态完全应用后再恢复过渡效果
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            // 再次强制重排，确保所有状态都已应用
            void sidebar.offsetHeight;
            if (mainContent) {
                void mainContent.offsetHeight;
            }

            // 恢复过渡效果
            sidebar.style.transition = '';
            if (mainContent) {
                mainContent.style.transition = '';
            }
            // 恢复子元素的过渡效果
            originalTransitions.forEach(function (transition, child) {
                child.style.transition = transition || '';
            });

            // 移除恢复标记
            delete sidebar.dataset.restoring;
        });
    });
}

/**
 * 展开/收起子菜单
 */
function toggleSubmenu(element) {
    var li = element.closest('li');
    if (li) {
        li.classList.toggle('expanded');
        
        // 保存展开状态
        var expandedMenus = JSON.parse(localStorage.getItem('expanded_menus') || '[]');
        var menuId = li.getAttribute('data-menu-id');
        
        if (li.classList.contains('expanded')) {
            if (!expandedMenus.includes(menuId)) {
                expandedMenus.push(menuId);
            }
        } else {
            var index = expandedMenus.indexOf(menuId);
            if (index > -1) {
                expandedMenus.splice(index, 1);
            }
        }
        
        localStorage.setItem('expanded_menus', JSON.stringify(expandedMenus));
    }
}

/**
 * 恢复子菜单展开状态
 */
function restoreSubmenuState() {
    var expandedMenus = JSON.parse(localStorage.getItem('expanded_menus') || '[]');
    
    expandedMenus.forEach(menuId => {
        var menu = document.querySelector(`[data-menu-id="${menuId}"]`);
        if (menu) {
            menu.classList.add('expanded');
        }
    });
}

// 页面加载完成后恢复子菜单状态
document.addEventListener('DOMContentLoaded', restoreSubmenuState);

/**
 * 添加触摸滑动支持
 */
function addTouchSupport(sidebar, overlay) {
    var touchStartX = 0;
    var touchEndX = 0;
    var threshold = 50; // 滑动阈值
    
    // 监听触摸开始事件
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });
    
    // 监听触摸结束事件
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].clientX;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        var swipeDistance = touchEndX - touchStartX;
        var isMobile = window.innerWidth <= 768;
        
        if (!isMobile) return;
        
        // 从左边缘向右滑动打开侧边栏
        if (touchStartX < 20 && swipeDistance > threshold) {
            openMobileSidebar();
        }
        // 从右向左滑动关闭侧边栏
        else if (swipeDistance < -threshold && sidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        }
    }
}

/**
 * 改进移动端侧边栏菜单点击体验
 */
function improveMobileMenuInteraction() {
    var menuLinks = document.querySelectorAll('.sidebar-menu-card a');
    var isMobile = window.innerWidth <= 768;
    
    if (!isMobile) return;
    
    menuLinks.forEach(function(link) {
        // 为移动端添加更大的点击区域
        link.style.minHeight = '48px';
        link.style.display = 'flex';
        link.style.alignItems = 'center';
        
        // 添加点击反馈
        link.addEventListener('touchstart', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        }, { passive: true });
        
        link.addEventListener('touchend', function() {
            var self = this;
            setTimeout(function() {
                self.style.backgroundColor = '';
            }, 150);
        }, { passive: true });
    });
}

// 监听窗口大小变化，改进移动端交互
window.addEventListener('resize', function() {
    improveMobileMenuInteraction();
});

// 初始化时调用
document.addEventListener('DOMContentLoaded', function() {
    improveMobileMenuInteraction();
});
