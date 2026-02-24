/**
 * 响应式表格处理脚本
 * 在小屏幕上将表格转换为卡片布局
 */

document.addEventListener('DOMContentLoaded', function() {
    initResponsiveTables();
    handleTableResize();
});

/**
 * 初始化响应式表格
 */
function initResponsiveTables() {
    const tableContainers = document.querySelectorAll('.table-responsive');
    
    tableContainers.forEach(container => {
        // 为每个表格容器添加响应式处理
        enhanceTableAccessibility(container);
        addTableInteraction(container);
    });
}

/**
 * 增强表格的可访问性
 */
function enhanceTableAccessibility(container) {
    const table = container.querySelector('table');
    if (!table) return;
    
    // 添加表格标题
    if (!table.querySelector('caption')) {
        const caption = document.createElement('caption');
        caption.className = 'sr-only';
        caption.textContent = '数据表格';
        table.appendChild(caption);
    }
    
    // 为表头添加scope属性
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        if (!header.hasAttribute('scope')) {
            header.setAttribute('scope', 'col');
        }
        
        // 添加排序指示器的ARIA标签
        const sortLink = header.querySelector('a');
        if (sortLink) {
            const isSorted = sortLink.closest('th').classList.contains('sorted-asc') || 
                           sortLink.closest('th').classList.contains('sorted-desc');
            
            if (isSorted) {
                const sortDirection = sortLink.closest('th').classList.contains('sorted-asc') ? 'ascending' : 'descending';
                sortLink.setAttribute('aria-label', `点击按${sortLink.textContent}排序，当前为${sortDirection}顺序`);
            } else {
                sortLink.setAttribute('aria-label', `点击按${sortLink.textContent}排序`);
            }
        }
    });
}

/**
 * 添加表格交互功能
 */
function addTableInteraction(container) {
    const table = container.querySelector('table');
    if (!table) return;
    
    // 添加行悬停效果
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
        
        // 移动端点击效果
        row.addEventListener('touchstart', function() {
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        }, { passive: true });
        
        row.addEventListener('touchend', function() {
            const self = this;
            setTimeout(() => {
                self.style.backgroundColor = '';
            }, 150);
        }, { passive: true });
    });
}

/**
 * 处理窗口大小变化
 */
function handleTableResize() {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            convertTablesIfNeeded();
        }, 250);
    });
    
    // 初始检查
    convertTablesIfNeeded();
}

/**
 * 根据屏幕尺寸转换表格布局
 */
function convertTablesIfNeeded() {
    const isMobile = window.innerWidth <= 380;
    const tableContainers = document.querySelectorAll('.table-responsive');
    
    tableContainers.forEach(container => {
        const table = container.querySelector('table');
        if (!table) return;
        
        if (isMobile) {
            convertToCardLayout(container, table);
        } else {
            restoreTableLayout(container, table);
        }
    });
}

/**
 * 将表格转换为卡片布局
 */
function convertToCardLayout(container, table) {
    // 检查是否已经转换
    if (container.querySelector('.table-card-container')) {
        return;
    }
    
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    if (rows.length === 0) return;
    
    // 隐藏原表格
    table.style.display = 'none';
    
    // 创建卡片容器
    const cardContainer = document.createElement('div');
    cardContainer.className = 'table-card-container';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const card = createTableRowCard(cells);
        cardContainer.appendChild(card);
    });
    
    container.appendChild(cardContainer);
}

/**
 * 创建表格行对应的卡片
 */
function createTableRowCard(cells) {
    const card = document.createElement('div');
    card.className = 'table-card';
    
    // 获取关键数据
    const id = cells[0]?.textContent.trim() || '';
    const username = cells[1]?.textContent.trim() || '';
    const statusCell = cells[8]; // 状态列
    const actionsCell = cells[9]; // 操作列
    
    // 创建卡片头部
    const header = document.createElement('div');
    header.className = 'table-card-header';
    
    // 头像
    const avatar = document.createElement('div');
    avatar.className = 'table-card-avatar';
    avatar.textContent = username.charAt(0).toUpperCase();
    
    // 标题信息
    const title = document.createElement('div');
    title.className = 'table-card-title';
    
    const name = document.createElement('div');
    name.className = 'table-card-name';
    name.textContent = username;
    
    const idText = document.createElement('div');
    idText.className = 'table-card-id';
    idText.textContent = `ID: ${id}`;
    
    title.appendChild(name);
    title.appendChild(idText);
    
    // 状态
    const status = document.createElement('div');
    status.className = 'table-card-status';
    
    if (statusCell) {
        const statusText = statusCell.textContent.trim();
        const isActive = statusText === '正常';
        status.classList.add(isActive ? 'active' : 'inactive');
        status.textContent = statusText;
    }
    
    header.appendChild(avatar);
    header.appendChild(title);
    header.appendChild(status);
    
    // 创建信息区域
    const info = document.createElement('div');
    info.className = 'table-card-info';
    
    // 添加其他信息
    const infoItems = [
        { label: '星夜币', value: cells[4]?.textContent.trim() || '0' },
        { label: '会员等级', value: cells[5]?.textContent.trim() || '普通用户' },
        { label: '注册时间', value: cells[6]?.textContent.trim() || '-' },
        { label: '最后登录', value: cells[7]?.textContent.trim() || '-' }
    ];
    
    infoItems.forEach(item => {
        const infoItem = document.createElement('div');
        infoItem.className = 'table-card-info-item';
        
        const label = document.createElement('div');
        label.className = 'table-card-info-label';
        label.textContent = item.label;
        
        const value = document.createElement('div');
        value.className = 'table-card-info-value';
        value.textContent = item.value;
        
        infoItem.appendChild(label);
        infoItem.appendChild(value);
        info.appendChild(infoItem);
    });
    
    // 创建操作区域
    const actions = document.createElement('div');
    actions.className = 'table-card-actions';
    
    if (actionsCell) {
        const buttons = actionsCell.querySelectorAll('.btn');
        buttons.forEach(btn => {
            const clonedBtn = btn.cloneNode(true);
            actions.appendChild(clonedBtn);
        });
    }
    
    card.appendChild(header);
    card.appendChild(info);
    card.appendChild(actions);
    
    return card;
}

/**
 * 恢复表格布局
 */
function restoreTableLayout(container, table) {
    // 移除卡片容器
    const cardContainer = container.querySelector('.table-card-container');
    if (cardContainer) {
        cardContainer.remove();
    }
    
    // 显示原表格
    table.style.display = '';
}

/**
 * 优化表格在移动端的滚动体验
 */
function optimizeTableScroll() {
    const tableContainers = document.querySelectorAll('.table-responsive');
    
    tableContainers.forEach(container => {
        let isScrolling;
        
        container.addEventListener('scroll', function() {
            // 添加滚动时的样式
            this.classList.add('scrolling');
            
            clearTimeout(isScrolling);
            isScrolling = setTimeout(() => {
                this.classList.remove('scrolling');
            }, 150);
        }, { passive: true });
        
        // 添加滚动提示
        if (container.scrollWidth > container.clientWidth) {
            addScrollHint(container);
        }
    });
}

/**
 * 添加滚动提示
 */
function addScrollHint(container) {
    if (container.querySelector('.scroll-hint')) {
        return;
    }
    
    const hint = document.createElement('div');
    hint.className = 'scroll-hint';
    hint.innerHTML = '← 左右滑动查看更多 →';
    hint.style.cssText = `
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        pointer-events: none;
        opacity: 0.8;
        z-index: 5;
    `;
    
    container.style.position = 'relative';
    container.appendChild(hint);
    
    // 3秒后自动隐藏提示
    setTimeout(() => {
        if (hint.parentNode) {
            hint.remove();
        }
    }, 3000);
    
    // 开始滚动时隐藏提示
    container.addEventListener('scroll', function() {
        if (hint.parentNode) {
            hint.remove();
        }
    }, { once: true, passive: true });
}

// 初始化滚动优化
document.addEventListener('DOMContentLoaded', optimizeTableScroll);
