/**
 * 仪表板交互脚本
 * 处理仪表板页面的各种交互效果
 */

document.addEventListener('DOMContentLoaded', function () {
    initCardAnimations();
    initNumberCounters();
    initRefreshButtons();
    initTooltips();
    initDropdowns();
    initTabs();
    initModals();
    initLoadingStates();
});

/**
 * 初始化卡片动画
 */
function initCardAnimations() {
    var cards = document.querySelectorAll('.dashboard-card, .stat-card');

    if (!('IntersectionObserver' in window)) {
        cards.forEach(function (card) {
            card.classList.add('animate-ready', 'animate-in');
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    cards.forEach(function (card) {
        card.classList.add('animate-ready');
        observer.observe(card);
    });
}

/**
 * 初始化数字计数器
 */
function initNumberCounters() {
    var counters = document.querySelectorAll('[data-counter]');

    if (!('IntersectionObserver' in window)) {
        counters.forEach(function (counter) {
            animateCounter(counter);
        });
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    counters.forEach(function (counter) {
        observer.observe(counter);
    });
}

/**
 * 动画计数
 */
function animateCounter(element) {
    var target = parseInt(element.getAttribute('data-counter'));
    var duration = parseInt(element.getAttribute('data-duration')) || 2000;
    var prefix = element.getAttribute('data-prefix') || '';
    var suffix = element.getAttribute('data-suffix') || '';
    
    var startTime = performance.now();
    
    function update(currentTime) {
        var elapsed = currentTime - startTime;
        var progress = Math.min(elapsed / duration, 1);
        
        // 使用 easeOutQuart 缓动函数
        var easeProgress = 1 - Math.pow(1 - progress, 4);
        var current = Math.floor(easeProgress * target);
        
        element.textContent = prefix + current.toLocaleString() + suffix;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = prefix + target.toLocaleString() + suffix;
        }
    }
    
    requestAnimationFrame(update);
}

/**
 * 初始化刷新按钮
 */
function initRefreshButtons() {
    document.querySelectorAll('.btn-refresh, [data-refresh]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var target = this.getAttribute('data-refresh') || '.dashboard-content';
            var container = document.querySelector(target);

            if (!container) return;

            this.classList.add('spinning');
            container.classList.add('loading');

            try {
                var response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var html = await response.text();
                container.innerHTML = html;

                // 重新初始化
                initCardAnimations();
                initNumberCounters();
            } catch (error) {
                console.error('Refresh failed:', error);
            } finally {
                this.classList.remove('spinning');
                container.classList.remove('loading');
            }
        });
    });
}

/**
 * 初始化工具提示
 */
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(function (element) {
        var tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = element.getAttribute('data-tooltip') || '';
        document.body.appendChild(tooltip);

        element.addEventListener('mouseenter', function () {
            var rect = element.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            tooltip.classList.add('show');
        });

        element.addEventListener('mouseleave', function () {
            tooltip.classList.remove('show');
        });
    });
}

/**
 * 初始化下拉菜单
 */
function initDropdowns() {
    document.querySelectorAll('.dropdown').forEach(function (dropdown) {
        var toggle = dropdown.querySelector('.dropdown-toggle');
        var menu = dropdown.querySelector('.dropdown-menu');

        if (!toggle || !menu) return;

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        // 点击外部关闭
        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });

        menu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });
}

/**
 * 初始化标签页
 */
function initTabs() {
    document.querySelectorAll('.tabs').forEach(function (tabs) {
        var nav = tabs.querySelector('.tabs-nav');
        var panels = tabs.querySelector('.tabs-panels');

        if (!nav || !panels) return;

        var navItems = nav.querySelectorAll('.tab-nav-item');
        var panelItems = panels.querySelectorAll('.tab-panel');

        navItems.forEach(function (item, index) {
            item.addEventListener('click', function () {
                // 移除所有激活状态
                navItems.forEach(function (i) { i.classList.remove('active'); });
                panelItems.forEach(function (p) { p.classList.remove('active'); });

                // 激活当前
                item.classList.add('active');
                if (panelItems[index]) {
                    panelItems[index].classList.add('active');
                }

                // 保存状态
                var tabId = tabs.getAttribute('data-tabs-id');
                if (tabId) {
                    localStorage.setItem('tab_' + tabId, String(index));
                }
            });
        });

        // 恢复保存的标签页
        var tabId = tabs.getAttribute('data-tabs-id');
        if (tabId) {
            var savedTab = localStorage.getItem('tab_' + tabId);
            var savedIndex = savedTab !== null ? parseInt(savedTab, 10) : 0;
            if (navItems[savedIndex]) {
                navItems[savedIndex].click();
            } else if (navItems[0]) {
                navItems[0].click();
            }
        }
    });
}

/**
 * 初始化模态框
 */
function initModals() {
    // 打开模态框
    document.querySelectorAll('[data-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modalId = btn.getAttribute('data-modal');
            var modal = document.getElementById(modalId);
            if (modal) {
                openModal(modal);
            }
        });
    });

    // 关闭按钮
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = btn.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });

    // 点击遮罩层关闭
    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    // ESC 键关闭
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.open').forEach(function (modal) {
                closeModal(modal);
            });
        }
    });
}

/**
 * 打开模态框
 */
function openModal(modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    // 聚焦第一个输入框
    setTimeout(function () {
        var firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    }, 100);
}

/**
 * 关闭模态框
 */
function closeModal(modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

/**
 * 初始化加载状态
 */
function initLoadingStates() {
    // 为所有异步操作按钮添加加载状态
    document.querySelectorAll('button, .btn').forEach(function (btn) {
        if (!btn.hasAttribute('data-no-loading')) {
            btn.addEventListener('click', function () {
                if (this.type === 'submit' || this.getAttribute('data-async')) {
                    this.dataset.originalText = this.textContent;
                }
            });
        }
    });
}

/**
 * 显示加载状态
 */
function showLoading(element, message) {
    if (message === void 0) {
        message = '加载中...';
    }
    if (!element) return;
    element.classList.add('loading');
    if (element.dataset.originalText) {
        element.textContent = message;
    }
}

/**
 * 隐藏加载状态
 */
function hideLoading(element) {
    if (!element) return;
    element.classList.remove('loading');
    if (element.dataset.originalText) {
        element.textContent = element.dataset.originalText;
    }
}

/**
 * 平滑滚动到元素
 */
function scrollToElement(element, offset) {
    if (offset === void 0) {
        offset = 80;
    }
    if (!element) return;
    var rect = element.getBoundingClientRect();
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    var targetY = rect.top + scrollTop - offset;

    window.scrollTo({
        top: targetY,
        behavior: 'smooth'
    });
}

/**
 * 复制到剪贴板
 */
async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            throw new Error('Clipboard API not available');
        }
        showNotification('success', '已复制到剪贴板');
    } catch (err) {
        // 降级方案
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('success', '已复制到剪贴板');
    }
}

/**
 * 显示通知
 */
function showNotification(type, message) {
    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(function () { notification.classList.add('show'); }, 10);
    setTimeout(function () {
        notification.classList.remove('show');
        setTimeout(function () { notification.remove(); }, 300);
    }, 3000);
}
